-- ============================================================================
-- 021_rbac_hardening.sql
-- Étape 1 de la sécurisation : RBAC, profils, invitations.
--
-- Corrige :
--   1. profiles : un membre pouvait modifier son propre role_id / status
--      (auto-promotion, auto-réactivation après suspension).
--   2. handle_new_auth_user lisait role_id / invited_by dans raw_user_meta_data,
--      que le CLIENT contrôle (signUp, auth.updateUser).
--   3. Délégation sans limite : un détenteur de invite_users / manage_users
--      pouvait attribuer n'importe quel rôle (Super Admin compris) et agir sur
--      des membres plus privilégiés que lui.
--   N3. La vue content_index contournait la RLS (titres/slugs des brouillons
--      lisibles par anon).
--
-- Règles appliquées (voir supabase/README.md, « Modèle de sécurité RBAC ») :
--   R1 personne ne modifie son propre rôle/statut, sauf invited → active
--   R2 cette activation exige une invitation pending non expirée (→ accepted)
--   R3 id, email, invited_by, created_at non modifiables via l'API
--   R4 le rôle d'un nouveau compte vient UNIQUEMENT de son invitation
--   R5 on n'attribue que des rôles dont on possède toutes les permissions ;
--      le rôle système (Super Admin) seulement par un Super Admin
--   R6 on n'agit sur un membre que si on pourrait lui attribuer son rôle actuel
--   R7 invitations : invited_by = soi + R5
--   R8 role_permissions / roles : rôles système réservés au Super Admin,
--      on n'accorde que des permissions qu'on possède, is_system_role figé
--
-- Migration écrite pour s'appliquer sur la base existante (create or replace,
-- drop ... if exists) et sans changement requis côté front.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. Fonctions d'autorisation
--    security definer : elles lisent profiles/roles/role_permissions sans être
--    bloquées par la RLS. search_path figé pour qu'on ne puisse pas leur faire
--    appeler une fonction homonyme placée dans un autre schéma.
-- ----------------------------------------------------------------------------

-- Super Admin = membre ACTIF dont le rôle est le rôle système.
create or replace function public.is_super_admin(p_user_id uuid)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1
    from public.profiles p
    join public.roles r on r.id = p.role_id
    where p.id = p_user_id
      and p.status = 'active'
      and r.is_system_role
  );
$$;

-- R5 — Non-escalade par délégation : p_actor peut-il attribuer p_role_id ?
--   * Super Admin actif : n'importe quel rôle existant ;
--   * sinon : membre actif, rôle NON système, et TOUTES les permissions du
--     rôle sont déjà détenues par p_actor (on ne donne pas ce qu'on n'a pas).
create or replace function public.can_assign_role(p_actor uuid, p_role_id uuid)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select case
    when p_actor is null or p_role_id is null then false
    when public.is_super_admin(p_actor) then
      exists (select 1 from public.roles where id = p_role_id)
    else
      exists (select 1 from public.profiles where id = p_actor and status = 'active')
      and exists (select 1 from public.roles r where r.id = p_role_id and not r.is_system_role)
      and not exists (
        select 1
        from public.role_permissions rp
        join public.permissions perm on perm.id = rp.permission_id
        where rp.role_id = p_role_id
          and not public.has_permission(p_actor, perm.key)
      )
  end;
$$;

-- Appelables par les utilisateurs connectés (nécessaire : les policies RLS
-- s'exécutent avec les droits de l'appelant) et par les Edge Functions.
-- Pas par anon : un visiteur n'a rien à tester.
revoke all on function public.is_super_admin(uuid) from public, anon;
revoke all on function public.can_assign_role(uuid, uuid) from public, anon;
grant execute on function public.is_super_admin(uuid) to authenticated, service_role;
grant execute on function public.can_assign_role(uuid, uuid) to authenticated, service_role;


-- ----------------------------------------------------------------------------
-- 2. R1, R2, R3, R6 — Garde sur les mises à jour de profiles
--
-- Pourquoi un trigger et pas une policy ? La RLS est une règle sur la LIGNE :
-- son WITH CHECK ne voit que la nouvelle version, il ne peut pas savoir si
-- role_id a CHANGÉ. Un trigger BEFORE UPDATE voit OLD et NEW.
--
-- Seules les requêtes API d'un visiteur ou d'un utilisateur connecté
-- (JWT role = anon / authenticated) sont contrôlées. Le SQL Editor (postgres,
-- pas de JWT) et les Edge Functions (service_role) sont des acteurs de
-- confiance : le SQL de secours du README continue de fonctionner.
-- ----------------------------------------------------------------------------
create or replace function public.guard_profile_update()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  v_jwt_role text := coalesce(
    nullif(current_setting('request.jwt.claims', true), '')::jsonb ->> 'role', '');
  v_actor uuid := auth.uid();
  v_invitation_id uuid;
begin
  if v_jwt_role not in ('anon', 'authenticated') then
    return new;
  end if;

  -- R3 : identité et traçabilité non modifiables
  if new.id is distinct from old.id
     or new.email is distinct from old.email
     or new.invited_by is distinct from old.invited_by
     or new.created_at is distinct from old.created_at then
    raise exception 'Les champs id, email, invited_by et created_at ne sont pas modifiables.'
      using errcode = '42501';
  end if;

  -- Ni rôle ni statut modifiés : simple édition (nom, bio, avatar) → OK
  if new.role_id is not distinct from old.role_id and new.status = old.status then
    return new;
  end if;

  -- R1 / R2 : sa propre ligne
  if old.id = v_actor then
    if old.status = 'invited' and new.status = 'active'
       and new.role_id is not distinct from old.role_id then
      update public.invitations
         set status = 'accepted'
       where id = (
         select i.id from public.invitations i
         where lower(i.email) = lower(old.email)
           and i.status = 'pending'
           and i.expires_at > now()
         order by i.sent_at desc
         limit 1
       )
      returning id into v_invitation_id;

      if v_invitation_id is null then
        raise exception 'Aucune invitation valide pour ce compte (révoquée ou expirée) : activation impossible. Demandez une nouvelle invitation.'
          using errcode = '42501';
      end if;
      return new;
    end if;

    raise exception 'Vous ne pouvez pas modifier votre propre rôle ni votre propre statut.'
      using errcode = '42501';
  end if;

  -- R6 / R5 : modification du rôle ou du statut d'un AUTRE membre
  if not public.has_permission(v_actor, 'manage_users') then
    raise exception 'Permission manage_users requise pour modifier le rôle ou le statut d''un membre.'
      using errcode = '42501';
  end if;

  if old.role_id is not null and not public.can_assign_role(v_actor, old.role_id) then
    raise exception 'Ce membre a un rôle plus élevé que le vôtre : vous ne pouvez pas le modifier.'
      using errcode = '42501';
  end if;

  if new.role_id is distinct from old.role_id
     and (new.role_id is null or not public.can_assign_role(v_actor, new.role_id)) then
    raise exception 'Vous ne pouvez attribuer que des rôles dont vous possédez toutes les permissions.'
      using errcode = '42501';
  end if;

  return new;
end;
$$;

revoke all on function public.guard_profile_update() from public, anon, authenticated;

drop trigger if exists trg_profiles_guard_update on public.profiles;
create trigger trg_profiles_guard_update
  before update on public.profiles
  for each row execute function public.guard_profile_update();


-- ----------------------------------------------------------------------------
-- 3. R4 — Création du profil : le rôle vient de l'invitation, jamais des
--    métadonnées (raw_user_meta_data = user_metadata, écrit par le client).
--    Sans invitation valide : profil sans rôle → aucune permission.
--    ⚠️ L'invitation doit donc exister AVANT la création du compte : l'Edge
--    Function invite-user l'insère maintenant avant d'appeler inviteUserByEmail.
-- ----------------------------------------------------------------------------
create or replace function public.handle_new_auth_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
  v_role_id uuid;
  v_invited_by uuid;
begin
  select i.role_id, i.invited_by
    into v_role_id, v_invited_by
  from public.invitations i
  where lower(i.email) = lower(new.email)
    and i.status = 'pending'
    and i.expires_at > now()
  order by i.sent_at desc
  limit 1;

  insert into public.profiles (id, email, full_name, role_id, status, invited_by)
  values (
    new.id,
    new.email,
    -- full_name reste un simple affichage : seule donnée reprise des métadonnées
    nullif(left(trim(new.raw_user_meta_data ->> 'full_name'), 120), ''),
    v_role_id,
    'invited',
    v_invited_by
  )
  on conflict (id) do nothing;
  return new;
end;
$$;

revoke all on function public.handle_new_auth_user() from public, anon, authenticated;


-- ----------------------------------------------------------------------------
-- 4. R7 — Invitations
--    Un inviteur ne crée que des invitations à son nom, pour des rôles qu'il
--    peut attribuer, au statut pending, expirant dans 7 jours max. Côté
--    client, il ne peut ensuite que les révoquer.
-- ----------------------------------------------------------------------------
drop policy if exists "invitations_insert_inviters" on public.invitations;
create policy "invitations_insert_inviters" on public.invitations
  for insert with check (
    public.has_permission(auth.uid(), 'invite_users')
    and invited_by = auth.uid()
    and status = 'pending'
    and expires_at <= now() + interval '7 days'
    and public.can_assign_role(auth.uid(), role_id)
  );

drop policy if exists "invitations_update_inviters" on public.invitations;
create policy "invitations_update_inviters" on public.invitations
  for update
  using (
    public.has_permission(auth.uid(), 'invite_users')
    and public.can_assign_role(auth.uid(), role_id)
  )
  with check (
    public.has_permission(auth.uid(), 'invite_users')
    and public.can_assign_role(auth.uid(), role_id)
    and status in ('revoked', 'expired')
  );

drop policy if exists "invitations_delete_inviters" on public.invitations;
create policy "invitations_delete_inviters" on public.invitations
  for delete using (
    public.has_permission(auth.uid(), 'invite_users')
    and public.can_assign_role(auth.uid(), role_id)
  );


-- ----------------------------------------------------------------------------
-- 5. R8 — Rôles et permissions
-- ----------------------------------------------------------------------------

-- role_permissions : manage_users requis ; un rôle système ne se modifie que
-- par un Super Admin ; on n'accorde qu'une permission qu'on détient.
drop policy if exists "role_permissions_write_managers" on public.role_permissions;
create policy "role_permissions_write_managers" on public.role_permissions
  for insert with check (
    public.has_permission(auth.uid(), 'manage_users')
    and (
      public.is_super_admin(auth.uid())
      or (
        not exists (
          select 1 from public.roles r
          where r.id = role_permissions.role_id and r.is_system_role
        )
        and exists (
          select 1 from public.permissions p
          where p.id = role_permissions.permission_id
            and public.has_permission(auth.uid(), p.key)
        )
      )
    )
  );

drop policy if exists "role_permissions_delete_managers" on public.role_permissions;
create policy "role_permissions_delete_managers" on public.role_permissions
  for delete using (
    public.has_permission(auth.uid(), 'manage_users')
    and (
      public.is_super_admin(auth.uid())
      or not exists (
        select 1 from public.roles r
        where r.id = role_permissions.role_id and r.is_system_role
      )
    )
  );

-- roles : is_system_role n'est plus écrivable via l'API. Les privilèges de
-- COLONNE ne fonctionnent que si le privilège de TABLE est retiré d'abord
-- (un GRANT UPDATE sur la table couvre toutes les colonnes).
revoke insert, update on public.roles from anon, authenticated;
grant insert (name, description), update (name, description) on public.roles to authenticated;

drop policy if exists "roles_update_managers" on public.roles;
create policy "roles_update_managers" on public.roles
  for update
  using (
    public.has_permission(auth.uid(), 'manage_users')
    and (not is_system_role or public.is_super_admin(auth.uid()))
  )
  with check (
    public.has_permission(auth.uid(), 'manage_users')
    and (not is_system_role or public.is_super_admin(auth.uid()))
  );


-- ----------------------------------------------------------------------------
-- 6. N3 — content_index respecte la RLS de l'appelant
--    Par défaut une vue s'exécute avec les droits de son PROPRIÉTAIRE
--    (postgres) → RLS des tables ignorée. security_invoker = true : la vue
--    applique les droits de celui qui l'interroge. Le front ne l'interroge
--    pas directement ; get_top_viewed (security definer) continue de marcher.
-- ----------------------------------------------------------------------------
alter view public.content_index set (security_invoker = true);
revoke all on public.content_index from anon, authenticated;
