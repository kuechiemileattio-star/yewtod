# Lot 01 — Authentification

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Tables `roles`, `permissions`, `users`, `sessions`, `password_reset_tokens`, `password_histories`, `invitations`, `activity_log` | `database/migrations/2026_11_15_1001*` à `1005*` |
| Fortify (routes redéclarées, vues françaises, limitation) | `app/Providers/FortifyServiceProvider.php`, `routes/auth.php`, `resources/views/auth/` |
| Invitations : jeton haché SHA-256, 7 jours, usage unique, révocation, annulation si l'email échoue | `app/Services/InvitationService.php`, `app/Mail/InvitationMail.php` |
| Onboarding (nom, bio, mot de passe) | `app/Http/Controllers/Auth/OnboardingController.php` |
| 2FA TOTP obligatoire, QR + clé, 8 codes de secours affichés une fois, régénération, désactivation | `app/Http/Middleware/EnsureTwoFactorEnabled.php`, `app/Http/Controllers/Dashboard/TwoFactorController.php` |
| Preuve d'accès à la boîte mail avant d'enrôler un appareil (ajout de sécurité) | `app/Services/TwoFactorEnrollmentCode.php` |
| Politique de mot de passe (§ 8.2) + historique des 5 derniers | `app/Services/PasswordPolicy.php`, `app/Rules/StrongPassword.php` |
| Mot de passe oublié sans énumération | `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `app/Actions/Fortify/ResetUserPassword.php` |
| Sessions serveur (30 min d'inactivité), « déconnecter les autres appareils » | `config/session.php`, `app/Services/SessionManager.php` |
| Journal d'audit sans secret (IP hachées HMAC) | `app/Services/Audit.php` |
| Emails de sécurité (2FA activée/désactivée, codes régénérés, mot de passe changé) | `app/Notifications/SecurityNotification.php` |
| Commande `yewtod:create-super-admin` | `app/Console/Commands/CreateSuperAdmin.php` |
| Traductions françaises (validation, auth, mots de passe) | `lang/fr/` |

## Choix expliqués

- **Pourquoi redéclarer les routes Fortify ?** Pour poser une limitation différente sur chaque route et remplacer l'envoi du lien de réinitialisation par une version qui répond toujours la même chose (Fortify répond « aucun utilisateur avec cet email », ce qui permet d'énumérer les comptes).
- **Temps de réponse constant à la connexion** : si l'email n'existe pas, on calcule quand même un hachage Argon2id factice. Sinon, la réponse « email inconnu » serait plus rapide et trahirait l'existence des comptes.
- **Limitation croissante** : 5/minute, puis 10/15 minutes, 20/heure par couple email + IP, et 100/heure par IP. Plus l'attaque dure, plus l'attente s'allonge.
- **Codes de secours « une seule fois »** : la route Fortify qui relit les codes n'est pas exposée ; ils ne sont montrés que dans la réponse qui suit leur génération (flash de session) et téléchargeables en `.txt` généré dans le navigateur.
- **Code email avant l'enrôlement** : sans lui, quelqu'un qui volerait seulement le mot de passe d'un membre dont la 2FA a été réinitialisée pourrait enregistrer son propre téléphone. Après l'onboarding, le lien d'invitation sert déjà de preuve.
- **Anti-rejeu TOTP** : Fortify mémorise en cache le dernier horodatage accepté ; un même code ne passe pas deux fois.
- **R4 dans le code** : l'onboarding ne lit que `name`, `bio`, `password` ; le rôle est recopié depuis l'invitation. Un `role_id` ajouté à la main dans le formulaire est ignoré (test à l'appui).

## Menaces traitées

Force brute (limitation), énumération de comptes (messages et temps identiques), vol de base (jetons hachés, secrets 2FA chiffrés, Argon2id), fixation de session (régénération), réutilisation d'invitation (verrou + statut), escalade via onboarding (R4), rejeu TOTP, journaux contenant des secrets (filtrage dans `Audit`).

## Vérifier

```bash
php artisan test tests/Feature/Auth
php artisan yewtod:create-super-admin
```

Puis connexion sur `/login` : la configuration de la 2FA est imposée. En local (`MAIL_MAILER=log`), le code email se lit dans `storage/logs/laravel.log`.
