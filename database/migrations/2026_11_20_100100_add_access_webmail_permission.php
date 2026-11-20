<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Décision D72 : permission « Accéder au webmail ».
 *
 * Le seeder de référence ne modifie jamais un rôle existant (pour respecter
 * les réglages faits depuis le dashboard). Sur une base déjà en service, la
 * nouvelle permission est donc ajoutée ici, puis donnée au rôle
 * « Administrateur » s'il existe. Le Super Admin l'a d'office (toutes les
 * permissions). Les autres rôles ne la reçoivent pas : un administrateur
 * peut l'accorder depuis « Utilisateurs & rôles ».
 */
return new class extends Migration
{
    private const KEY = 'access_webmail';

    public function up(): void
    {
        if (! DB::table('permissions')->where('key', self::KEY)->exists()) {
            DB::table('permissions')->insert([
                'key' => self::KEY,
                'label' => 'Accéder au webmail',
                'description' => 'Lien vers la messagerie de l\'équipe (Roundcube LWS) ; chaque boîte demande ses propres identifiants',
            ]);
        }

        $permissionId = DB::table('permissions')->where('key', self::KEY)->value('id');
        $roleId = DB::table('roles')->where('slug', 'administrateur')->value('id');

        if ($roleId !== null && ! DB::table('permission_role')->where(['role_id' => $roleId, 'permission_id' => $permissionId])->exists()) {
            DB::table('permission_role')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        // La suppression de la permission retire aussi ses attributions (cascade).
        DB::table('permissions')->where('key', self::KEY)->delete();
    }
};
