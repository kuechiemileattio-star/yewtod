<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Livewire\Dashboard\MediaManager;
use App\Livewire\Dashboard\Newsletter;
use App\Livewire\Dashboard\Pages;
use App\Livewire\Dashboard\Settings;
use App\Models\Book;
use App\Models\Content;
use App\Models\Media;
use App\Models\NewsletterSubscriber;
use App\Models\Page;
use App\Models\SocialLink;
use App\Services\EventCounter;
use App\Services\MediaLibrary;
use App\Services\SiteAdmin;
use App\Services\SiteSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
 * Lot 08 — administration (cahier § 4.1, § 4.8, § 4.9, § 5.9, § 10 P2, § 13.1, bugs 2, 6, 7, 16).
 */

describe('paramètres du site (bug n° 2)', function () {
    it('applique nom, description SEO et réflexion sur le site public, date fixée automatiquement', function () {
        Livewire::actingAs(member('administrateur'))->test(Settings::class)
            ->set('form.site_name', 'Mon Média')->set('form.seo_description', 'Une description SEO précise.')
            ->set('form.reflection_text', 'Une pensée.')->set('form.reflection_author', 'Moi')
            ->call('save')->assertDispatched('toast', type: 'success');
        expect(app(SiteSettings::class)->get('reflection')['date'])->toBe(now()->toDateString());
        Content::factory()->create();
        $this->get('/')->assertSee('<title>Mon Média</title>', false)->assertSee('Une pensée.');
        // Description par défaut : utilisée par les pages qui n'en ont pas (ici la 404).
        $this->get('/inconnue')->assertSee('<meta name="description" content="Une description SEO précise.">', false);
    });

    it('enregistre logo et favicon (WebP, pas de SVG) et les sert sur le site', function () {
        Storage::fake('public');
        Livewire::actingAs(member('administrateur'))->test(Settings::class)
            ->set('logo', UploadedFile::fake()->image('logo.png', 300, 80))->set('favicon', UploadedFile::fake()->image('f.png', 64, 64))
            ->call('save')->assertDispatched('toast', type: 'success');
        $site = app(SiteSettings::class);
        expect($site->get('logo_path'))->toEndWith('.webp')->and($site->get('favicon_path'))->toEndWith('.webp');
        $this->get('/meet')->assertSee($site->logoUrl(), false)->assertSee('<link rel="icon" href="'.$site->faviconUrl().'">', false);

        Livewire::actingAs(member('administrateur'))->test(Settings::class)
            ->set('logo', UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
            ->call('save')->assertDispatched('toast', type: 'error');
    });

    it('ordonne les réseaux sociaux et refuse les liens non https', function () {
        $admin = member('administrateur');
        app(SiteAdmin::class)->saveSocialLinks($admin, [['platform' => 'YouTube', 'url' => 'https://youtube.com/@x'], ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/x']]);
        expect(SocialLink::query()->orderBy('position')->pluck('platform')->all())->toBe(['YouTube', 'LinkedIn']);
        $html = $this->get('/')->getContent();
        expect(strpos($html, 'youtube.com/@x'))->toBeLessThan(strpos($html, 'linkedin.com/in/x'));
        expect(fn () => app(SiteAdmin::class)->saveSocialLinks($admin, [['platform' => 'X', 'url' => 'javascript:alert(1)']]))->toThrow(ValidationException::class);
        expect(fn () => app(SiteAdmin::class)->saveSocialLinks($admin, [['platform' => 'X', 'url' => 'http://x.com']]))->toThrow(ValidationException::class);
    });

    it('est réservé à manage_settings', function () {
        $this->actingAs(member('editeur'))->get('/dashboard/parametres')->assertForbidden();
        expect(fn () => app(SiteAdmin::class)->saveSettings(member('editeur'), ['site_name' => 'x', 'seo_description' => 'y']))->toThrow(AuthorizationException::class);
    });
});

describe('pages éditables (bug n° 7)', function () {
    it('édite Meet (dont la FAQ) et l\'accueil, avec les clés cohérentes', function () {
        Livewire::actingAs(member('administrateur'))->test(Pages::class)
            ->set('form.hero_title', 'Nouveau titre Meet')->set('form.expertise', "Sociologie\nDémographie")
            ->set('form.faq.0.q', 'Question modifiée ?')->call('save')->assertDispatched('toast', type: 'success');
        expect(Page::query()->where('key', 'meet')->exists())->toBeTrue();
        $this->get('/meet')->assertSee('Nouveau titre Meet')->assertSee('Démographie')->assertSee('Question modifiée ?');

        Livewire::actingAs(member('administrateur'))->test(Pages::class)->set('tab', 'home')
            ->set('form.pillars.1.title', 'Du terrain, encore')->call('save');
        $this->get('/')->assertSee('Du terrain, encore');
    });

    it('n\'enregistre aucune clé inconnue et refuse les pages hors liste', function () {
        app(SiteAdmin::class)->savePage(member('administrateur'), 'footer', ['cta_title' => 'A', 'cta_text' => 'B', 'about' => 'C', 'script' => '<script>']);
        expect(Page::query()->where('key', 'footer')->first()->content)->not->toHaveKey('script');
        expect(fn () => app(SiteAdmin::class)->savePage(member('administrateur'), 'admin', []))->toThrow(InvalidArgumentException::class);
    });

    it('est réservé à manage_pages', function () {
        $this->actingAs(member('editeur'))->get('/dashboard/pages')->assertForbidden();
    });
});

describe('médiathèque (bug n° 6)', function () {
    it('envoie une image (WebP) et un PDF, refuse SVG et faux PDF', function () {
        Storage::fake('public');
        $editor = member('editeur');
        $img = app(MediaLibrary::class)->upload($editor, UploadedFile::fake()->image('photo.jpg', 1200, 800));
        expect($img->kind)->toBe('image')->and($img->path)->toEndWith('.webp')->and($img->width)->toBe(1200);
        $pdf = app(MediaLibrary::class)->upload($editor, UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.4\n%%EOF"));
        expect($pdf->kind)->toBe('document');
        foreach ([['x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'], ['x.pdf', '<?php system($_GET["c"]);'], ['x.html', '<html><script></script></html>']] as [$name, $body]) {
            expect(fn () => app(MediaLibrary::class)->upload($editor, UploadedFile::fake()->createWithContent($name, $body)))->toThrow(ValidationException::class);
        }
    });

    it('refuse un fichier de plus de 20 Mo', function () {
        app(MediaLibrary::class)->upload(member('editeur'), UploadedFile::fake()->create('big.pdf', 21 * 1024, 'application/pdf'));
    })->throws(ValidationException::class);

    it('édite le texte alternatif, repère et supprime les orphelins', function () {
        Storage::fake('public');
        $editor = member('editeur');
        $used = app(MediaLibrary::class)->upload($editor, UploadedFile::fake()->image('a.jpg'));
        $orphan = app(MediaLibrary::class)->upload($editor, UploadedFile::fake()->image('b.jpg'));
        Content::factory()->create()->forceFill(['cover_path' => $used->path])->save();
        Livewire::actingAs($editor)->test(MediaManager::class)->call('select', $used->id)->set('alt', 'Une description')->call('saveAlt');
        expect($used->fresh()->alt)->toBe('Une description')->and(MediaLibrary::isUsed($used))->toBeTrue()->and(MediaLibrary::isUsed($orphan))->toBeFalse();
        $this->travel(2)->days();
        expect(app(MediaLibrary::class)->deleteOrphans($editor))->toBe(1);
        expect(Media::query()->pluck('id')->all())->toBe([$used->id]);
    });

    it('est réservé à manage_media', function () {
        $this->actingAs(member('moderateur-collaborations'))->get('/dashboard/mediatheque')->assertForbidden();
        expect(fn () => app(MediaLibrary::class)->upload(member('moderateur-collaborations'), UploadedFile::fake()->image('a.jpg')))->toThrow(AuthorizationException::class);
    });
});

describe('newsletter (dashboard)', function () {
    it('liste, filtre, exporte en CSV sûr et supprime sur demande RGPD', function () {
        foreach (['a@x.fr' => 'confirmed', '=cmd@x.fr' => 'pending'] as $email => $status) {
            (new NewsletterSubscriber)->forceFill(['email' => $email, 'status' => $status])->save();
        }
        $admin = member('administrateur');
        $this->actingAs($admin)->get('/dashboard/newsletter')->assertOk()->assertSee('a@x.fr')->assertSee('Synchronisation Brevo désactivée');
        $csv = $this->actingAs($admin)->get('/dashboard/newsletter/export')->assertOk()->streamedContent();
        expect($csv)->toContain('"\'=cmd@x.fr"');
        Livewire::actingAs($admin)->test(Newsletter::class)->call('delete', NewsletterSubscriber::query()->where('email', 'a@x.fr')->value('id'));
        expect(NewsletterSubscriber::query()->count())->toBe(1);
        $this->actingAs(member('editeur'))->get('/dashboard/newsletter')->assertForbidden();
        $this->actingAs(member('editeur'))->get('/dashboard/newsletter/export')->assertForbidden();
    });
});

describe('vue d\'ensemble (cahier § 4.1, bug n° 16)', function () {
    it('compte les publiés, y compris les programmés échus, avec « +N ce mois »', function () {
        Content::factory()->type(ContentType::Article)->count(2)->create(['published_at' => now()]);
        $due = Content::factory()->type(ContentType::Article)->status(ContentStatus::Scheduled)->make();
        $due->forceFill(['scheduled_at' => now()->subHour()])->save();
        Content::factory()->type(ContentType::Article)->status(ContentStatus::Draft)->create();
        Book::factory()->create(['published_at' => now()->subYear()]);
        $html = $this->actingAs(member('editeur'))->get('/dashboard')->assertOk()->getContent();
        expect($html)->toMatch('/Articles publiés.*?>3<\/p>/s')->toContain('Livres publiés')->toContain('Publications par mois');
    });

    it('affiche le top 3 des 7 derniers jours', function () {
        $a = Content::factory()->create(['title' => 'Très lu']);
        $b = Content::factory()->create(['title' => 'Peu lu']);
        $counter = app(EventCounter::class);
        $counter->record($a, 'view');
        session()->flush();
        $counter->record($a, 'view');
        session()->flush();
        $counter->record($b, 'view');
        $html = $this->actingAs(member('editeur'))->get('/dashboard')->getContent();
        expect(strpos($html, 'Très lu'))->toBeLessThan(strpos($html, 'Peu lu'));
    });

    it('n\'expose pas de lien d\'édition aux membres sans manage_articles', function () {
        Content::factory()->create(['title' => 'Contenu récent']);
        $this->actingAs(member('moderateur-collaborations'))->get('/dashboard')->assertOk()->assertSee('Contenu récent')->assertDontSee('/modifier', false);
    });
});
