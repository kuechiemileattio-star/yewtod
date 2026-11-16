<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Données de DÉMONSTRATION (cahier § 12.4) : refusées hors environnement
 * local, quelle que soit la façon dont le seeder est lancé.
 *
 * Comptes créés (mot de passe commun affiché dans docs/INSTALLATION.md,
 * 2FA déjà configurée avec un secret de démonstration) :
 *   superadmin@yewtod.test, admin@yewtod.test, editeur@yewtod.test,
 *   contributeur@yewtod.test, collaborations@yewtod.test
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Demo-Yewtod-Local-2026!';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoSeeder est réservé à l\'environnement local.');
        }

        $this->call(ReferenceSeeder::class);

        $accounts = [
            ['superadmin@yewtod.test', 'Awa Superadmin', 'super-admin'],
            ['admin@yewtod.test', 'Bruno Administrateur', 'administrateur'],
            ['editeur@yewtod.test', 'Chloé Éditrice', 'editeur'],
            ['contributeur@yewtod.test', 'David Contributeur', 'contributeur'],
            ['collaborations@yewtod.test', 'Élise Modératrice', 'moderateur-collaborations'],
        ];

        foreach ($accounts as [$email, $name, $role]) {
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }
            User::factory()->role($role)->email($email)->withTwoFactor()->create(['name' => $name])
                ->forceFill(['password' => self::PASSWORD])->save();
        }

        foreach ($this->demoSeeders() as $seeder) {
            $this->call($seeder);
        }
    }

    /** @return list<class-string<Seeder>> */
    protected function demoSeeders(): array
    {
        return array_values(array_filter([
            // Complété au fil des lots (contenus, livres, avis, collaborations, newsletter).
            DemoContentSeeder::class,
            DemoBookSeeder::class,
        ], fn ($c) => class_exists($c)));
    }
}
