<?php

namespace Database\Seeders;

use App\Enums\ContentType;
use App\Models\Book;
use App\Models\Collaboration;
use App\Models\Content;
use App\Models\NewsletterSubscriber;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Avis (3 statuts), demandes de collaboration et abonnés de DÉMONSTRATION (cahier § 12.4). */
class DemoPublicWritesSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoPublicWritesSeeder est réservé à l\'environnement local.');
        }
        if (Review::query()->exists() || Collaboration::query()->exists()) {
            return;
        }
        $editor = User::query()->where('email', 'editeur@yewtod.test')->first();
        $targets = Content::query()->published()->ofType(ContentType::Article)->limit(3)->get()->concat(Book::query()->published()->limit(2)->get());
        foreach ($targets as $i => $target) {
            foreach (['approved', 'approved', 'pending', 'rejected'] as $j => $status) {
                (new Review(['name' => fake('fr_FR')->firstName(), 'body' => fake('fr_FR')->sentences(random_int(2, 4), true)]))->forceFill([
                    'reviewable_type' => $target->getMorphClass(), 'reviewable_id' => $target->getKey(), 'status' => $status,
                    'moderated_by' => $status === 'pending' ? null : $editor?->id, 'moderated_at' => $status === 'pending' ? null : now()->subDays($j),
                    'created_at' => now()->subDays(10 - $i - $j),
                ])->save();
            }
        }
        foreach (array_keys(Collaboration::KINDS) as $i => $kind) {
            (new Collaboration([
                'name' => fake('fr_FR')->name(), 'organization' => $i % 2 ? fake('fr_FR')->company() : null, 'email' => 'demande'.$i.'@exemple.fr',
                'subject' => fake('fr_FR')->sentence(5), 'kind' => $kind, 'description' => fake('fr_FR')->paragraphs(2, true),
            ]))->forceFill(['status' => ['new', 'new', 'in_progress', 'archived'][$i % 4], 'created_at' => now()->subDays($i * 3)])->save();
        }
        foreach (['confirmed', 'confirmed', 'confirmed', 'pending', 'unsubscribed'] as $i => $status) {
            (new NewsletterSubscriber)->forceFill([
                'email' => 'abonne'.$i.'@exemple.fr', 'status' => $status,
                'confirmed_at' => $status !== 'pending' ? now()->subDays(20 - $i) : null,
                'unsubscribed_at' => $status === 'unsubscribed' ? now()->subDays(2) : null,
                'unsubscribe_token_hash' => $status !== 'pending' ? hash('sha256', 'demo-'.$i) : null,
            ])->save();
        }
    }
}
