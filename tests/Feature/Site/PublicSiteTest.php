<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\ContentEvent;
use App\Services\EventCounter;
use App\Services\SiteSettings;
use App\Support\Charts\SvgChart;
use App\Support\TextStructurer;
use App\Support\VideoEmbed;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
 * Lot 05 — site public (cahier § 2, § 5.2, § 5.3, § 8.6 « lecture publique », § 10, bugs 4, 14, 15).
 */

function work(ContentType $type = ContentType::Article, ContentStatus $status = ContentStatus::Published, array $attrs = [], array $force = []): Content
{
    $c = Content::factory()->type($type)->status($status)->make($attrs);
    $c->forceFill($force);
    if ($status === ContentStatus::Scheduled) {
        $c->forceFill(['scheduled_at' => now()->addDay()]);
    }
    $c->save();

    return $c;
}

describe('visibilité publique (aucun brouillon atteignable)', function () {
    it('affiche une publication publiée', function () {
        $c = work();
        $this->get($c->publicUrl())->assertOk()->assertSee($c->title);
    });

    it('renvoie 404 pour un brouillon, une relecture, un archivé ou un programmé futur', function (ContentStatus $status) {
        $c = work(status: $status);
        $this->get($c->publicUrl())->assertNotFound();
        $this->get('/works')->assertDontSee($c->title);
        $this->get('/')->assertDontSee($c->title);
    })->with([ContentStatus::Draft, ContentStatus::Review, ContentStatus::Scheduled, ContentStatus::Archived]);

    it('montre un programmé échu avant même la bascule automatique', function () {
        $c = work(status: ContentStatus::Scheduled);
        $c->forceFill(['scheduled_at' => now()->subMinute()])->save();
        $this->get($c->publicUrl())->assertOk();
    });

    it('refuse un type inconnu ou un slug d\'un autre type', function () {
        $c = work(ContentType::Report);
        $this->get('/works/inconnu/'.$c->slug)->assertNotFound();
        $this->get('/works/articles/'.$c->slug)->assertNotFound();
    });

    it('ne liste pas les brouillons dans le sitemap, le flux RSS ni la recherche', function () {
        $draft = work(status: ContentStatus::Draft, attrs: ['title' => 'Brouillon secret zzz']);
        $pub = work(attrs: ['title' => 'Publication visible zzz']);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee($pub->publicUrl(), false)->assertDontSee($draft->slug);
        $this->get('/feed')->assertOk()->assertSee('Publication visible zzz')->assertDontSee('Brouillon secret');
        $this->getJson('/recherche?q=zzz')->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.title', 'Publication visible zzz');
    });
});

describe('accueil', function () {
    it('affiche l\'état vide sans publication', function () {
        $this->get('/')->assertOk()->assertSee('Les premières publications arrivent bientôt');
    });

    it('fait piloter « Travaux mis en avant » par la case mise en avant (bug n° 4)', function () {
        $recent = work(attrs: ['title' => 'La plus récente']);
        $recent->forceFill(['published_at' => now()])->save();
        foreach (range(1, 5) as $i) {
            work(attrs: ['title' => "Ordinaire $i"])->forceFill(['published_at' => now()->subDays($i)])->save();
        }
        $star = work(attrs: ['title' => 'Vieille pépite', 'is_featured' => true]);
        $star->forceFill(['published_at' => now()->subYear()])->save();

        $html = $this->get('/')->assertOk()->getContent();
        $block = substr($html, strpos($html, 'Travaux mis en avant'), 8000);
        expect($block)->toContain('Vieille pépite');
    });

    it('masque la réflexion de la semaine si elle est vide', function () {
        work();
        $this->get('/')->assertDontSee('Réflexion de la semaine');
        app(SiteSettings::class)->set('reflection', ['text' => 'Penser contre soi-même.', 'author' => 'X', 'date' => null]);
        $this->get('/')->assertSee('Réflexion de la semaine')->assertSee('Penser contre soi-même.');
    });
});

describe('catalogue /works : filtres en liste blanche', function () {
    it('filtre par catégorie, axe, recherche et affiche « N résultats sur M »', function () {
        work(ContentType::Report, attrs: ['title' => 'Rapport sur le logement', 'themes' => ['societe']]);
        work(ContentType::Article, attrs: ['title' => 'Article sur le climat', 'themes' => ['environnement'], 'tags' => ['carbone']]);

        $this->get('/works?categorie=rapports')->assertSee('Rapport sur le logement')->assertDontSee('Article sur le climat')->assertSee('1 résultat sur 2');
        $this->get('/works?axe=environnement')->assertSee('Article sur le climat')->assertDontSee('Rapport sur le logement');
        $this->get('/works?q=carbone')->assertSee('Article sur le climat')->assertDontSee('Rapport sur le logement');
    });

    it('ignore les valeurs hors liste blanche et résiste aux tentatives d\'injection SQL', function (string $query) {
        work(attrs: ['title' => 'Toujours là']);
        $this->get('/works?'.$query)->assertOk();
    })->with([
        'tri=title;DROP TABLE contents',
        'tri=(SELECT SLEEP(5))',
        'categorie=articles\' OR 1=1 --',
        'types[]=x&types[]=articles%27--',
        'axe=1)+OR+(1=1',
        'serie=1 OR 1=1',
        'auteur=-1',
        'du=2026-13-45&au=\'; DELETE FROM users; --',
        'q=%27%20OR%20%271%27=%271',
        'q[]=tableau',
        'page=99999999',
    ]);

    it('ne renvoie rien pour une injection dans la recherche, sans erreur', function () {
        work(attrs: ['title' => 'Titre A']);
        $this->get('/works?q='.urlencode("' OR '1'='1"))->assertOk()->assertSee('0 résultat sur 1');
        // Les jokers LIKE saisis sont échappés : « % » ne correspond pas à tout.
        $this->get('/works?q=%25')->assertOk()->assertSee('0 résultat sur 1');
        expect(Content::query()->count())->toBe(1);
    });

    it('trie selon la liste blanche (titre A–Z)', function () {
        work(attrs: ['title' => 'Zèbre']);
        work(attrs: ['title' => 'Abeille']);
        $html = $this->get('/works?tri=titre')->getContent();
        expect(strpos($html, 'Abeille'))->toBeLessThan(strpos($html, 'Zèbre'));
    });

    it('pagine avec une limite haute (12 par page)', function () {
        Content::factory()->count(14)->create();
        $this->get('/works')->assertSee('14 résultats sur 14')->assertSee('Page 1 sur 2');
    });
});

describe('page détail', function () {
    it('propose les 3 gabarits, la citation APA et BibTeX, le JSON-LD et les balises Open Graph', function () {
        work(attrs: ['title' => 'Autre article']);
        $article = work(attrs: ['title' => 'Mon article']);
        $this->get($article->publicUrl())->assertSee('"@type":"Article"', false)->assertSee('og:title', false)
            ->assertSee('Partager et citer')->assertSee('@misc{', false);
        $report = work(ContentType::Report, attrs: ['meta' => ['authors' => ['A. Auteur'], 'doi' => '10.1234/abcd']]);
        $this->get($report->publicUrl())->assertSee('"@type":"ScholarlyArticle"', false)->assertSee('https://doi.org/10.1234/abcd', false)->assertSee('A. Auteur');
        $series = work(ContentType::DocumentarySeries, attrs: ['meta' => ['trailer_url' => 'https://youtu.be/aqz-KE-bpKQ']]);
        $this->get($series->publicUrl())->assertSee('youtube-nocookie.com/embed/aqz-KE-bpKQ', false)->assertSee('end=120', false)->assertSee('Continuer sur YouTube');
    });

    it('échappe le titre et nettoie le HTML des sections (XSS)', function () {
        $c = work(attrs: ['title' => '<script>alert(1)</script>Titre', 'sections' => [['title' => 'S', 'content' => '<p onclick="x()">ok</p><script>alert(2)</script><a href="javascript:alert(3)">lien</a>']]]);
        $html = $this->get($c->publicUrl())->assertOk()->getContent();
        expect($html)->not->toContain('<script>alert(1)')->not->toContain('alert(2)')->not->toContain('javascript:alert')->not->toContain('onclick=')
            ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;Titre');
    });

    it('structure automatiquement un texte brut (intertitres et puces), échappé', function () {
        $html = TextStructurer::toHtml("Un intertitre\nUne phrase longue qui se termine par un point et ne doit pas devenir un intertitre.\n• premier & point\n• second");
        expect($html)->toContain('<h3>Un intertitre</h3>')->toContain('<ul><li>premier &amp; point</li><li>second</li></ul>');
    });

    it('n\'affiche que des liens http(s) même si la base contenait autre chose', function () {
        $c = work(ContentType::ResearchNote, attrs: ['meta' => ['links' => [['label' => 'Piège', 'url' => 'javascript:alert(1)'], ['label' => 'Bon', 'url' => 'https://example.org']]]]);
        $this->get($c->publicUrl())->assertDontSee('javascript:alert', false)->assertSee('https://example.org', false);
    });

    it('rend les graphiques des visualisations en SVG côté serveur, données échappées', function () {
        $c = work(ContentType::DataVisualization, attrs: ['meta' => ['blocks' => [
            ['title' => 'Bloc', 'chart_type' => 'bar', 'data' => ['labels' => ['<img src=x onerror=alert(1)>', 'B'], 'series' => [['name' => 'S', 'values' => [1, 2]]]]],
        ]]]);
        $html = $this->get($c->publicUrl())->assertOk()->getContent();
        expect($html)->toContain('<svg class="chart-svg')->not->toContain('<img src=x')->toContain('&lt;img src=x');
    });

    it('rend les 6 types de graphiques sans erreur', function (string $type) {
        $chart = new SvgChart($type, ['labels' => ['a', 'b', 'c'], 'series' => [['name' => 'x', 'values' => [1, -2, 3]], ['name' => 'y', 'values' => [2, 2, 2]]]]);
        expect($chart->render())->toStartWith('<svg')->toEndWith('</svg>');
    })->with(['bar', 'line', 'area', 'stacked', 'scatter', 'pie']);

    it('liste les épisodes publiés d\'une série et propose le retour à la série', function () {
        $series = work(ContentType::DocumentarySeries, attrs: ['title' => 'Ma série']);
        $ep = work(ContentType::DocumentaryEpisode, attrs: ['title' => 'Épisode publié'], force: ['parent_id' => $series->id, 'episode_number' => 1]);
        work(ContentType::DocumentaryEpisode, ContentStatus::Draft, ['title' => 'Épisode caché'], ['parent_id' => $series->id, 'episode_number' => 2]);
        $this->get($series->publicUrl())->assertSee('Épisode publié')->assertDontSee('Épisode caché');
        $this->get($ep->publicUrl())->assertSee('Retour à la série');
    });
});

describe('statistiques (cahier § 5.3)', function () {
    it('compte une vue une seule fois par session', function () {
        $c = work();
        $this->get($c->publicUrl());
        $this->get($c->publicUrl());
        expect($c->fresh()->views_count)->toBe(1)
            ->and(ContentEvent::query()->where('kind', 'view')->count())->toBe(1);
    });

    it('ne compte jamais un contenu invisible', function () {
        $c = work(status: ContentStatus::Draft);
        expect(app(EventCounter::class)->record($c, 'view'))->toBeFalse();
        expect(ContentEvent::query()->count())->toBe(0);
    });

    it('refuse un type d\'événement hors liste', function () {
        app(EventCounter::class)->record(work(), 'like');
    })->throws(InvalidArgumentException::class);

    it('plafonne les événements par IP (anti-gonflement)', function () {
        $counter = app(EventCounter::class);
        $items = Content::factory()->count(EventCounter::PER_IP_PER_MINUTE + 3)->create();
        $recorded = $items->filter(fn ($c) => $counter->record($c, 'view'))->count();
        expect($recorded)->toBe(EventCounter::PER_IP_PER_MINUTE);
    });

    it('la base refuse un type d\'événement inconnu (contrainte CHECK)', function () {
        DB::table('content_events')->insert(['eventable_type' => 'x', 'eventable_id' => 1, 'kind' => 'hack']);
    })->throws(QueryException::class);
});

describe('téléchargement', function () {
    beforeEach(fn () => Storage::fake('local'));

    it('force le téléchargement d\'un document publié et le compte', function () {
        Storage::disk('local')->put('documents/a.pdf', '%PDF-1.4 test');
        $c = work(ContentType::Report);
        $c->forceFill(['file_path' => 'documents/a.pdf'])->save();
        $res = $this->get($c->downloadUrl())->assertOk();
        expect($res->headers->get('Content-Disposition'))->toStartWith('attachment;');
        expect($c->fresh()->downloads_count)->toBe(1);
    });

    it('refuse le document d\'un brouillon (404)', function () {
        Storage::disk('local')->put('documents/b.pdf', '%PDF-1.4');
        $c = work(ContentType::Report, ContentStatus::Draft);
        $c->forceFill(['file_path' => 'documents/b.pdf'])->save();
        $this->get('/works/rapports/'.$c->slug.'/telecharger')->assertNotFound();
    });

    it('refuse un chemin de fichier qui sortirait du dossier prévu', function () {
        Storage::disk('local')->put('secret.txt', 'secret');
        $c = work(ContentType::Experiment, attrs: ['meta' => ['downloads' => [['label' => 'x', 'path' => 'downloads/../secret.txt']]]]);
        $this->get('/works/experimentations/'.$c->slug.'/fichiers/0')->assertNotFound();
    });

    it('limite les téléchargements par IP', function () {
        $c = work();
        foreach (range(1, 30) as $i) {
            $this->get('/works/articles/'.$c->slug.'/telecharger');
        }
        $this->get('/works/articles/'.$c->slug.'/telecharger')->assertStatus(429);
    });
});

describe('aperçu réel d\'un brouillon (URL signée)', function () {
    it('affiche le brouillon à un gestionnaire, en noindex', function () {
        $c = work(status: ContentStatus::Draft, attrs: ['title' => 'Brouillon à relire']);
        $url = URL::temporarySignedRoute('content.preview', now()->addMinutes(30), ['content' => $c->id]);
        $this->actingAs(member('editeur'))->get($url)->assertOk()->assertSee('Brouillon à relire')
            ->assertSee('Aperçu réel')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('noindex', false);
    });

    it('refuse une URL expirée ou non signée', function () {
        $c = work(status: ContentStatus::Draft);
        $user = member('editeur');
        $expired = URL::temporarySignedRoute('content.preview', now()->subMinute(), ['content' => $c->id]);
        $this->actingAs($user)->get($expired)->assertForbidden();
        $this->actingAs($user)->get('/apercu/'.$c->id)->assertForbidden();
    });

    it('exige d\'être connecté et d\'avoir la permission', function () {
        $c = work(status: ContentStatus::Draft);
        $url = URL::temporarySignedRoute('content.preview', now()->addMinutes(30), ['content' => $c->id]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(member('moderateur-collaborations'))->get($url)->assertForbidden();
    });
});

describe('pages et SEO', function () {
    it('sert Meet, les mentions légales et la confidentialité', function () {
        $this->get('/meet')->assertOk()->assertSee('Le projet en questions')->assertSee('Le fondateur');
        $this->get('/mentions-legales')->assertOk()->assertSee('Hébergement')->assertSee('LWS');
        $this->get('/confidentialite')->assertOk()->assertSee('Aucun cookie de suivi');
    });

    it('renvoie une vraie 404 avec recherche, dernières publications et en-têtes de sécurité (bug n° 14)', function () {
        $c = work(attrs: ['title' => 'Dernière parue']);
        $this->get('/une-url-inconnue')->assertNotFound()->assertSee('Cette page est introuvable')
            ->assertSee('name="q"', false)->assertSee('Dernière parue')
            ->assertHeader('X-Frame-Options', 'DENY');
    });

    it('rend les métadonnées côté serveur (bug n° 15) et le robots.txt pointe le sitemap', function () {
        $c = work(attrs: ['seo_title' => 'Titre SEO', 'seo_description' => 'Description SEO']);
        $this->get($c->publicUrl())->assertSee('<title>Titre SEO — Yewtod SS</title>', false)
            ->assertSee('<meta name="description" content="Description SEO">', false)
            ->assertSee('<link rel="canonical" href="'.$c->publicUrl().'">', false)
            ->assertSee('application/rss+xml', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'))->assertSee('Disallow: /dashboard');
    });

    it('invalide le cache du sitemap à la publication', function () {
        $this->get('/sitemap.xml');
        $c = work(attrs: ['title' => 'Nouvelle']);
        $this->get('/sitemap.xml')->assertSee($c->slug);
    });

    it('limite la recherche globale aux contenus visibles et à 2 caractères minimum', function () {
        work(status: ContentStatus::Draft, attrs: ['title' => 'Caché']);
        $this->getJson('/recherche?q=C')->assertJsonCount(0, 'results');
        $this->getJson('/recherche?q=Cach')->assertJsonCount(0, 'results');
    });
});

describe('vidéos', function () {
    it('n\'intègre que des URL YouTube/Vimeo/MP4 valides', function () {
        expect(VideoEmbed::parse('javascript:alert(1)'))->toBeNull()
            ->and(VideoEmbed::parse('https://evil.example/watch?v=aqz-KE-bpKQ'))->toBeNull()
            ->and(VideoEmbed::parse('https://www.youtube.com/watch?v=aqz-KE-bpKQ"onload=x')['embed'])->toBe('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ?rel=0&modestbranding=1&start=0&end=120')
            ->and(VideoEmbed::parse('https://vimeo.com/123456789')['kind'])->toBe('vimeo');
    });
});
