<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Jobs\SyncNewsletterWithBrevo;
use App\Livewire\Dashboard\Collaborations;
use App\Livewire\Dashboard\Moderation;
use App\Mail\NewsletterConfirmationMail;
use App\Models\Book;
use App\Models\Collaboration;
use App\Models\Content;
use App\Models\NewsletterSubscriber;
use App\Models\Review;
use App\Services\CollaborationService;
use App\Services\NewsletterService;
use App\Support\DashboardMenu;
use App\Support\SafeCsv;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/*
 * Lot 07 — écritures publiques (cahier § 2.7, § 2.8, § 4.5, § 4.6, § 5.4–5.6, § 8.4, § 13.1, bug n° 11).
 */

function article(ContentStatus $status = ContentStatus::Published): Content
{
    return Content::factory()->type(ContentType::Article)->status($status)->create();
}

function collabInput(array $o = []): array
{
    return array_merge(['name' => 'Ada', 'organization' => 'Labo', 'email' => 'ada@example.org', 'subject' => 'Une idée', 'kind' => 'recherche', 'description' => 'Description confidentielle.'], $o);
}

describe('avis des lecteurs', function () {
    it('crée un avis toujours en attente, sans le publier', function () {
        $a = article();
        $this->post("/works/articles/{$a->slug}/avis", ['name' => 'Léa', 'body' => 'Très éclairant.', 'status' => 'approved', 'created_at' => '2000-01-01'])
            ->assertRedirect($a->publicUrl().'#avis');
        $r = Review::query()->firstOrFail();
        expect($r->status)->toBe('pending')->and($r->created_at->year)->toBe(now()->year)->and($r->ip_hash)->toHaveLength(64)->not->toBe('127.0.0.1');
        $this->get($a->publicUrl())->assertDontSee('Très éclairant.')->assertSee('sera publié après relecture', false);
    });

    it('affiche uniquement les avis approuvés', function () {
        $a = article();
        foreach (['approved' => 'Avis visible', 'pending' => 'Avis attente', 'rejected' => 'Avis rejeté'] as $status => $body) {
            (new Review(['name' => 'X', 'body' => $body]))->forceFill(['reviewable_type' => Content::class, 'reviewable_id' => $a->id, 'status' => $status])->save();
        }
        $this->get($a->publicUrl())->assertSee('Avis visible')->assertDontSee('Avis attente')->assertDontSee('Avis rejeté');
    });

    it('n\'accepte des avis que sur un article ou un livre publié (décision 6)', function () {
        $draft = article(ContentStatus::Draft);
        $this->post("/works/articles/{$draft->slug}/avis", ['name' => 'X', 'body' => 'Y'])->assertNotFound();
        $report = Content::factory()->type(ContentType::Report)->create();
        $this->post("/works/rapports/{$report->slug}/avis", ['name' => 'X', 'body' => 'Y'])->assertNotFound();
        $book = Book::factory()->create();
        $this->post("/books/{$book->slug}/avis", ['name' => 'X', 'body' => 'Sur un livre'])->assertRedirect();
        expect(Review::query()->where('reviewable_type', Book::class)->count())->toBe(1);
    });

    it('valide le nom (1–80) et le texte (1–2 000)', function (array $data) {
        $a = article();
        $this->post("/works/articles/{$a->slug}/avis", $data)->assertSessionHasErrorsIn('review');
        expect(Review::query()->count())->toBe(0);
    })->with([[['name' => '', 'body' => 'ok']], [['name' => str_repeat('a', 81), 'body' => 'ok']], [['name' => 'ok', 'body' => str_repeat('b', 2001)]], [['name' => 'ok', 'body' => '   ']]]);

    it('échappe le texte d\'un avis approuvé (XSS)', function () {
        $a = article();
        (new Review(['name' => '<b>x</b>', 'body' => '<script>alert(1)</script>']))->forceFill(['reviewable_type' => Content::class, 'reviewable_id' => $a->id, 'status' => 'approved'])->save();
        $this->get($a->publicUrl())->assertDontSee('<script>alert(1)', false)->assertSee('&lt;script&gt;', false);
    });

    it('ne permet jamais de modifier le texte d\'un avis', function () {
        $r = (new Review(['name' => 'X', 'body' => 'Original']))->forceFill(['reviewable_type' => Content::class, 'reviewable_id' => article()->id]);
        $r->save();
        $r->body = 'Modifié';
        $r->save();
    })->throws(LogicException::class);
});

describe('anti-spam des 4 formulaires', function () {
    it('rejette un envoi dont le champ piège est rempli', function () {
        $a = article();
        $this->post("/works/articles/{$a->slug}/avis", ['name' => 'Bot', 'body' => 'Spam', 'website' => 'http://spam'])->assertSessionHasErrorsIn('review', 'form');
        $this->post('/collaborations', collabInput(['website' => 'x']))->assertSessionHasErrors('form');
        $this->post('/newsletter', ['email' => 'a@b.fr', 'website' => 'x'])->assertSessionHasErrorsIn('newsletter', 'form');
        expect(Review::query()->count() + Collaboration::query()->count() + NewsletterSubscriber::query()->count())->toBe(0);
    });

    it('vérifie Turnstile côté serveur quand la clé est configurée', function () {
        config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $this->post('/newsletter', ['email' => 'a@b.fr', 'cf-turnstile-response' => 'mauvais'])->assertSessionHasErrorsIn('newsletter', 'form');
        Mail::fake();
        $this->post('/newsletter', ['email' => 'a@b.fr', 'cf-turnstile-response' => 'bon'])->assertSessionHasNoErrors();
        Http::assertSentCount(2);
        // Sans jeton : refusé sans même appeler Cloudflare.
        $this->post('/newsletter', ['email' => 'c@d.fr'])->assertSessionHasErrorsIn('newsletter', 'form');
    });

    it('se désactive proprement sans clé Turnstile (aucun appel, aucune erreur)', function () {
        Mail::fake();
        $this->post('/newsletter', ['email' => 'a@b.fr'])->assertSessionHasNoErrors();
        $this->get('/collaborations')->assertOk()->assertDontSee('cf-turnstile', false);
    });

    it('limite les envois par IP', function () {
        Mail::fake();
        foreach (range(1, 5) as $i) {
            $this->post('/newsletter', ['email' => "n{$i}@exemple.fr"]);
        }
        $this->post('/newsletter', ['email' => 'n6@exemple.fr'])->assertStatus(429);
    });
});

describe('collaborations', function () {
    it('enregistre une demande « Nouveau », description chiffrée en base', function () {
        Storage::fake('local');
        $this->post('/collaborations', collabInput(['status' => 'archived']))->assertRedirect('/collaborations');
        $c = Collaboration::query()->firstOrFail();
        expect($c->status)->toBe('new')->and($c->description)->toBe('Description confidentielle.');
        $raw = DB::table('collaborations')->value('description');
        expect($raw)->not->toContain('confidentielle');
        $this->get('/collaborations')->assertSee('Demande envoyée');
    });

    it('accepte au plus 3 pièces jointes vérifiées sur leur contenu, stockées en privé', function () {
        Storage::fake('local');
        $pdf = fn () => UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n%%EOF");
        $this->post('/collaborations', collabInput() + ['attachments' => [$pdf(), $pdf()]])->assertSessionHasNoErrors();
        $files = Collaboration::query()->firstOrFail()->files;
        expect($files)->toHaveCount(2)->and($files[0]->path)->toStartWith('collaborations/');
        Storage::disk('local')->assertExists($files[0]->path);

        $this->post('/collaborations', collabInput() + ['attachments' => [$pdf(), $pdf(), $pdf(), $pdf()]])->assertSessionHasErrors('attachments');
        $this->post('/collaborations', collabInput() + ['attachments' => [UploadedFile::fake()->createWithContent('x.pdf', '<?php echo 1;')]])->assertSessionHasErrors('attachments');
        $this->post('/collaborations', collabInput() + ['attachments' => [UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]])->assertSessionHasErrors('attachments');
        expect(Collaboration::query()->count())->toBe(1);
    });

    it('valide les longueurs et le type', function (array $bad) {
        $this->post('/collaborations', collabInput($bad))->assertSessionHasErrors();
    })->with([[['name' => '']], [['email' => 'pas-un-email']], [['kind' => 'autre']], [['description' => str_repeat('x', 5001)]], [['subject' => str_repeat('s', 201)]]]);

    it('neutralise les formules dans l\'export CSV (bug n° 11)', function () {
        expect(SafeCsv::cell('=HYPERLINK("http://x")'))->toBe('"\'=HYPERLINK(""http://x"")"')
            ->and(SafeCsv::cell('+33 6'))->toBe('"\'+33 6"')->and(SafeCsv::cell('-1'))->toBe('"\'-1"')->and(SafeCsv::cell('@SUM(A1)'))->toBe('"\'@SUM(A1)"')
            ->and(SafeCsv::cell("a;b\nc"))->toBe("\"a;b\nc\"");
        app(CollaborationService::class)->submit(collabInput(['name' => '=1+1', 'description' => "ligne 1\nligne 2; \"x\""]), [], null);
        $res = $this->actingAs(member('moderateur-collaborations'))->get('/dashboard/collaborations/export')->assertOk();
        $csv = $res->streamedContent();
        expect($csv)->toStartWith(SafeCsv::BOM)->toContain('"\'=1+1"')->toContain("\"ligne 1\nligne 2; \"\"x\"\"\"");
    });

    it('réserve le dashboard et l\'export à manage_collaborations', function () {
        $this->actingAs(member('editeur'))->get('/dashboard/collaborations')->assertForbidden();
        $this->actingAs(member('editeur'))->get('/dashboard/collaborations/export')->assertForbidden();
        $this->actingAs(member('moderateur-collaborations'))->get('/dashboard/collaborations')->assertOk();
    });

    it('sert les pièces jointes par lien signé de 10 minutes, membre autorisé uniquement', function () {
        Storage::fake('local');
        app(CollaborationService::class)->submit(collabInput(), [UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n%%EOF")], null);
        $file = Collaboration::query()->firstOrFail()->files->first();
        $url = URL::temporarySignedRoute('dashboard.collaborations.file', now()->addMinutes(10), ['file' => $file->id]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(member('editeur'))->get($url)->assertForbidden();
        $mod = member('moderateur-collaborations');
        $this->actingAs($mod)->get($url)->assertOk();
        $this->actingAs($mod)->get('/dashboard/collaborations/fichiers/'.$file->id)->assertForbidden();
        $this->travel(11)->minutes();
        $this->actingAs($mod)->get($url)->assertForbidden();
    });

    it('change le statut (Nouveau → En cours → Archivé) et purge après 24 mois', function () {
        Storage::fake('local');
        $c = app(CollaborationService::class)->submit(collabInput(), [], null);
        Livewire::actingAs(member('moderateur-collaborations'))->test(Collaborations::class)->call('changeStatus', $c->id, 'in_progress');
        expect($c->fresh()->status)->toBe('in_progress');
        Livewire::actingAs(member('moderateur-collaborations'))->test(Collaborations::class)->call('changeStatus', $c->id, 'hack')->assertDispatched('toast', type: 'error');
        $c->forceFill(['created_at' => now()->subMonths(25)])->save();
        $this->artisan('yewtod:purge-public-data')->assertSuccessful();
        expect(Collaboration::query()->count())->toBe(0);
    });
});

describe('newsletter (double opt-in)', function () {
    it('normalise l\'email, envoie la confirmation et répond pareil dans tous les cas', function () {
        Mail::fake();
        $first = $this->from('/')->post('/newsletter', ['email' => '  Lea@Exemple.FR '])->assertRedirect('/');
        $sub = NewsletterSubscriber::query()->firstOrFail();
        expect($sub->email)->toBe('lea@exemple.fr')->and($sub->status)->toBe('pending')->and($sub->confirm_token_hash)->toHaveLength(64);
        Mail::assertSent(NewsletterConfirmationMail::class, 1);
        $sub->forceFill(['status' => 'confirmed'])->save();
        $second = $this->from('/')->post('/newsletter', ['email' => 'lea@exemple.fr']);
        expect($second->getSession()->get('newsletter_status'))->toBe($first->getSession()->get('newsletter_status'));
        Mail::assertSent(NewsletterConfirmationMail::class, 1); // aucun email révélateur pour une adresse déjà confirmée
    });

    it('confirme avec le jeton (stocké haché), refuse un jeton expiré', function () {
        Mail::fake();
        Queue::fake();
        app(NewsletterService::class)->subscribe('a@b.fr', null);
        $token = null;
        Mail::assertSent(NewsletterConfirmationMail::class, function ($m) use (&$token) {
            $token = $m->token;

            return true;
        });
        expect(NewsletterSubscriber::query()->value('confirm_token_hash'))->toBe(hash('sha256', $token));
        $this->travel(49)->hours();
        $this->get('/newsletter/confirmer/'.$token)->assertSee('Lien invalide ou expiré');
        $this->travelBack();
        $this->get('/newsletter/confirmer/'.$token)->assertSee('Inscription confirmée');
        expect(NewsletterSubscriber::query()->value('status'))->toBe('confirmed');
        Queue::assertPushed(SyncNewsletterWithBrevo::class);
        $this->get('/newsletter/confirmer/'.$token)->assertSee('Lien invalide'); // usage unique
    });

    it('désinscrit en un clic et ne réactive jamais silencieusement', function () {
        Mail::fake();
        Queue::fake();
        app(NewsletterService::class)->subscribe('a@b.fr', null);
        $confirm = null;
        Mail::assertSent(NewsletterConfirmationMail::class, function ($m) use (&$confirm) {
            $confirm = $m->token;

            return true;
        });
        $unsubscribe = app(NewsletterService::class)->confirm($confirm);
        $this->get('/newsletter/desinscription/'.$unsubscribe)->assertSee('Confirmer la désinscription');
        $this->post('/newsletter/desinscription/'.$unsubscribe)->assertSee('Vous êtes désinscrit');
        expect(NewsletterSubscriber::query()->value('status'))->toBe('unsubscribed');
        // Réinscription : repasse « en attente » avec un nouvel email de confirmation.
        app(NewsletterService::class)->subscribe('a@b.fr', null);
        expect(NewsletterSubscriber::query()->value('status'))->toBe('pending');
        Mail::assertSent(NewsletterConfirmationMail::class, 2);
    });

    it('purge les inscriptions non confirmées après 7 jours', function () {
        Mail::fake();
        app(NewsletterService::class)->subscribe('a@b.fr', null);
        $this->travel(8)->days();
        $this->artisan('yewtod:purge-public-data')->assertSuccessful();
        expect(NewsletterSubscriber::query()->count())->toBe(0);
    });

    it('synchronise Brevo seulement si la clé existe', function () {
        $sub = new NewsletterSubscriber;
        $sub->forceFill(['email' => 'a@b.fr', 'status' => 'confirmed'])->save();
        (new SyncNewsletterWithBrevo($sub->id))->handle();
        Http::assertNothingSent();

        config(['services.brevo.key' => 'cle', 'services.brevo.list_id' => '7']);
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);
        (new SyncNewsletterWithBrevo($sub->id))->handle();
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/contacts') && $r['listIds'] === [7] && $r->hasHeader('api-key', 'cle'));
        expect($sub->fresh()->brevo_synced_at)->not->toBeNull();
    });
});

describe('modération', function () {
    it('approuve un avis de publication en traçant qui et quand, sans changer le texte', function () {
        $a = article();
        $r = (new Review(['name' => 'X', 'body' => 'Texte']))->forceFill(['reviewable_type' => Content::class, 'reviewable_id' => $a->id]);
        $r->save();
        $editor = member('editeur');
        Livewire::actingAs($editor)->test(Moderation::class)->call('approve', $r->id)->assertDispatched('toast', type: 'success');
        $r->refresh();
        expect($r->status)->toBe('approved')->and($r->moderated_by)->toBe($editor->id)->and($r->moderated_at)->not->toBeNull()->and($r->body)->toBe('Texte');
    });

    it('limite la file aux avis que le membre peut modérer', function () {
        $a = article();
        $b = Book::factory()->create();
        foreach ([[Content::class, $a->id, 'Avis article'], [Book::class, $b->id, 'Avis livre']] as [$type, $id, $body]) {
            (new Review(['name' => 'X', 'body' => $body]))->forceFill(['reviewable_type' => $type, 'reviewable_id' => $id])->save();
        }
        $contrib = member('contributeur'); // manage_articles sans manage_books
        Livewire::actingAs($contrib)->test(Moderation::class)->assertSee('Avis article')->assertDontSee('Avis livre');
        $bookReview = Review::query()->where('reviewable_type', Book::class)->firstOrFail();
        Livewire::actingAs($contrib)->test(Moderation::class)->call('approve', $bookReview->id)->assertStatus(404);
        expect($bookReview->fresh()->status)->toBe('pending');
        $this->actingAs(member('moderateur-collaborations'))->get('/dashboard/moderation')->assertForbidden();
    });

    it('affiche les pastilles du menu (avis en attente, nouvelles collaborations)', function () {
        $a = article();
        (new Review(['name' => 'X', 'body' => 'Y']))->forceFill(['reviewable_type' => Content::class, 'reviewable_id' => $a->id])->save();
        app(CollaborationService::class)->submit(collabInput(), [], null);
        $menu = collect(DashboardMenu::for(member('administrateur')))->flatMap(fn ($g) => $g['items'])->keyBy('route');
        expect($menu['dashboard.moderation']['badge'])->toBe(1)->and($menu['dashboard.collaborations']['badge'])->toBe(1);
    });
});
