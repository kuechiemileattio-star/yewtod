<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit;
use App\Services\PasswordPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Création interactive du PREMIER Super Admin (cahier § 12.4). Refuse de
 * s'exécuter s'il en existe déjà un : les suivants passent par une invitation.
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'yewtod:create-super-admin';

    protected $description = 'Crée le premier Super Admin (interactif)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $role = Role::query()->where('slug', Role::SUPER_ADMIN)->firstOrFail();

        if (User::query()->where('role_id', $role->id)->exists()) {
            $this->error('Un Super Admin existe déjà. Les suivants doivent être invités depuis le dashboard.');

            return self::FAILURE;
        }

        $name = text('Nom affiché', required: true, validate: fn (string $v) => mb_strlen($v) > 120 ? '120 caractères maximum.' : null);
        $email = Str::lower(trim(text('Adresse email', required: true, validate: fn (string $v) => filter_var($v, FILTER_VALIDATE_EMAIL) && strlen($v) <= 254 ? null : 'Adresse invalide.')));
        $plain = password('Mot de passe (12 caractères min., majuscule, minuscule, chiffre, symbole)', required: true);
        $confirmation = password('Confirmez le mot de passe', required: true);

        $validator = Validator::make(
            ['email' => $email, 'password' => $plain, 'password_confirmation' => $confirmation],
            ['email' => ['unique:users,email'], 'password' => PasswordPolicy::rules(null, $name, $email)],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $plain, $role) {
            $user = new User(['name' => $name]);
            $user->forceFill(['email' => $email, 'role_id' => $role->id, 'status' => UserStatus::Active, 'password' => ''])->save();
            PasswordPolicy::apply($user, $plain);

            return $user;
        });

        Audit::record('user.super_admin_created_cli', $user, ['email' => $email], $user);

        $this->info("Super Admin créé : {$email}. À la première connexion, la double authentification sera exigée.");

        return self::SUCCESS;
    }
}
