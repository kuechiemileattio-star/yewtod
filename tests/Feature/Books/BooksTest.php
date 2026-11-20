<?php

use App\Enums\ContentStatus;
use App\Livewire\Dashboard\Books;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
 * Lot 06 — livres (cahier § 2.6, § 3.1, § 4.4, décision 5, bugs 9 et 13).
 */

function bookData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Un livre', 'author' => 'Une Autrice', 'category' => 'histoire', 'review' => 'Un avis argumenté.',
    ], $overrides);
}

describe('enregistrement côté serveur', function () {
    it('crée un livre avec slug automatique unique et prix en euros', function () {
        $user = member('editeur');
        $a = app(BookService::class)->save($user, null, bookData(['price_print' => '19,90']), 'publish');
        $b = app(BookService::class)->save($user, null, bookData(), 'draft');
        expect($a->slug)->toBe('un-livre')->and($b->slug)->toBe('un-livre-2')
            ->and($a->price_print)->toBe('19.90')->and(Book::euros($a->price_print))->toBe("19,90\u{202F}€")
            ->and($a->status)->toBe(ContentStatus::Published)->and($a->created_by)->toBe($user->id);
    });

    it('accepte un slug manuel (modifiable pour les livres)', function () {
        $book = app(BookService::class)->save(member('editeur'), null, bookData(['slug' => 'Mon Slug Perso']), 'draft');
        expect($book->slug)->toBe('mon-slug-perso');
    });

    it('exige l\'auteur, l\'avis personnel et une catégorie de la liste', function (array $bad, string $field) {
        app(BookService::class)->save(member('editeur'), null, bookData($bad), 'draft');
    })->with([
        [['author' => ''], 'author'],
        [['review' => ''], 'review'],
        [['category' => 'astrologie'], 'category'],
        [['difficulty' => 'facile'], 'difficulty'],
        [['price_print' => '-3'], 'price_print'],
        [['month' => '13'], 'month'],
        [['doi' => 'pas-un-doi'], 'doi'],
    ])->throws(ValidationException::class);

    it('refuse les liens d\'achat qui ne sont pas http(s) (bug n° 12)', function (string $url) {
        app(BookService::class)->save(member('editeur'), null, bookData(['buy_print_url' => $url]), 'draft');
    })->with(['javascript:alert(1)', 'data:text/html,x', 'ftp://exemple.org'])->throws(ValidationException::class);

    it('refuse un membre sans manage_books', function () {
        app(BookService::class)->save(member('contributeur'), null, bookData(), 'draft');
    })->throws(AuthorizationException::class);

    it('ignore les champs protégés envoyés par le client (affectation de masse)', function () {
        $book = app(BookService::class)->save(member('editeur'), null, bookData(['status' => 'published', 'views_count' => 999, 'created_by' => 1, 'ebook_path' => '../../.env']), 'draft');
        expect($book->status)->toBe(ContentStatus::Draft)->and($book->views_count)->toBe(0)->and($book->ebook_path)->toBeNull();
    });

    it('relie les ouvrages similaires par identifiant (bug n° 9)', function () {
        $other = Book::factory()->create(['title' => 'Ancien titre']);
        $book = app(BookService::class)->save(member('editeur'), null, bookData(['related' => [$other->id, 999999]]), 'publish');
        $other->forceFill(['title' => 'Nouveau titre'])->save();
        expect($book->related()->pluck('title')->all())->toBe(['Nouveau titre']);
    });

    it('programme puis publie automatiquement à l\'échéance', function () {
        $book = app(BookService::class)->save(member('editeur'), null, bookData(['scheduled_at' => now()->addHour()->setTimezone('Europe/Paris')->format('Y-m-d H:i')]), 'schedule');
        expect($book->status)->toBe(ContentStatus::Scheduled);
        $this->travel(2)->hours();
        $this->artisan('yewtod:publish-scheduled')->assertSuccessful();
        expect($book->fresh()->status)->toBe(ContentStatus::Published);
    });

    it('la base refuse un prix négatif ou une catégorie inconnue (CHECK)', function (array $attrs) {
        Book::factory()->make()->forceFill($attrs)->save();
    })->with([[['price_print' => -1]], [['category' => 'x']], [['month' => 0]]])->throws(QueryException::class);
});

describe('écran du dashboard', function () {
    it('est réservé à manage_books (route et composant)', function () {
        $this->actingAs(member('contributeur'))->get('/dashboard/livres')->assertForbidden();
        $this->actingAs(member('editeur'))->get('/dashboard/livres')->assertOk()->assertSee('Livres');
    });

    it('crée un livre depuis la fenêtre modale, avec couverture et ebook privés', function () {
        Storage::fake('public');
        Storage::fake('local');
        $pdf = UploadedFile::fake()->createWithContent('livre.pdf', "%PDF-1.4\n%%EOF");
        Livewire::actingAs(member('editeur'))->test(Books::class)
            ->call('create')
            ->set('form.title', 'Livre modal')->set('form.author', 'A. B.')->set('form.review', 'Très bon.')
            ->set('form.price_ebook', '9,99')
            ->set('cover', UploadedFile::fake()->image('c.png', 400, 600))
            ->set('ebook', $pdf)
            ->call('save', 'publish')
            ->assertHasNoErrors();
        $book = Book::query()->where('title', 'Livre modal')->firstOrFail();
        expect($book->ebook_path)->toStartWith('ebooks/')->and(Storage::disk('local')->exists($book->ebook_path))->toBeTrue()
            ->and($book->cover_path)->toEndWith('.webp')->and($book->price_ebook)->toBe('9.99');
    });

    it('applique les signets du PDF à la composition', function () {
        Livewire::actingAs(member('editeur'))->test(Books::class)->call('create')
            ->call('applyPdfInfo', 210, [['title' => 'Chapitre 1', 'page' => 3], ['title' => '<b>Chapitre 2</b>', 'page' => 40]])
            ->assertSet('form.pages', 210)->assertSet('form.composition.1.title', 'Chapitre 2');
    });

    it('ne propose plus d\'analyse IA de l\'ebook (fonction retirée, D71)', function () {
        $book = Book::factory()->create();
        Livewire::actingAs(member('editeur'))->test(Books::class)->call('edit', $book->id)->set('tab', 'ebook')
            ->assertDontSee('Analyser avec l\'IA')->assertDontSee('Gemini');
        expect(method_exists(Books::class, 'analyzeWithAi'))->toBeFalse();
    });
});

describe('pages publiques', function () {
    it('n\'affiche que les livres publiés', function () {
        $pub = Book::factory()->create(['title' => 'Livre public']);
        $draft = Book::factory()->status(ContentStatus::Draft)->create(['title' => 'Livre caché']);
        $this->get('/books')->assertOk()->assertSee('Livre public')->assertDontSee('Livre caché');
        $this->get($pub->publicUrl())->assertOk();
        $this->get($draft->publicUrl())->assertNotFound();
    });

    it('affiche une fiche 100 % en français, prix en euros, sans bouton d\'achat sans lien (bug n° 13)', function () {
        $book = Book::factory()->create(['price_print' => '21.50', 'buy_print_url' => null, 'price_ebook' => '8', 'buy_ebook_url' => 'https://exemple.org/achat']);
        $html = $this->get($book->publicUrl())->assertOk()->assertSee('Ce livre')->assertSee('Version papier')->assertSee('21,50', false)
            ->assertSee('"@type":"Book"', false)->assertSee('"priceCurrency":"EUR"', false)->getContent();
        foreach (['This Book', 'Buy Now', 'Search By Title', 'USD', '$'] as $english) {
            expect(str_contains(strip_tags($html), $english))->toBeFalse();
        }
        // Un seul bouton « Acheter » dans « Ce livre » : celui de l'ebook (la version papier n'a pas de lien).
        expect(substr_count($html, 'class="btn-primary">Acheter'))->toBe(1);
    });

    it('filtre par catégorie, auteur, année et recherche, en liste blanche', function () {
        Book::factory()->create(['title' => 'Histoire A', 'category' => 'histoire', 'author' => 'Marc', 'year' => 2001]);
        Book::factory()->create(['title' => 'Physique B', 'category' => 'physique', 'author' => 'Lise', 'year' => 2010]);
        $this->get('/books?categorie=physique')->assertSee('Physique B')->assertDontSee('Histoire A');
        $this->get('/books?auteur=Marc')->assertSee('Histoire A')->assertDontSee('Physique B');
        $this->get('/books?annee=2010')->assertSee('Physique B')->assertDontSee('Histoire A');
        $this->get('/books?q=Lise')->assertSee('Physique B')->assertDontSee('Histoire A');
        $this->get('/books?categorie='.urlencode("' OR 1=1 --").'&tri=DROP&annee=20x1')->assertOk()->assertSee('Histoire A');
    });

    it('« À lire aussi » : sélection manuelle, sinon la même catégorie', function () {
        $book = Book::factory()->create(['category' => 'histoire']);
        $same = Book::factory()->create(['category' => 'histoire', 'title' => 'Même catégorie']);
        Book::factory()->create(['category' => 'physique', 'title' => 'Autre catégorie']);
        $this->get($book->publicUrl())->assertSee('Même catégorie')->assertDontSee('Autre catégorie');
        $manual = Book::factory()->create(['category' => 'physique', 'title' => 'Choix manuel']);
        $book->related()->sync([$manual->id => ['position' => 0]]);
        $this->get($book->publicUrl())->assertSee('Choix manuel')->assertDontSee('Même catégorie');
    });

    it('sert les extraits et l\'ebook depuis le disque privé, jamais pour un brouillon', function () {
        Storage::fake('local');
        Storage::disk('local')->put('excerpts/x.pdf', '%PDF-1.4');
        Storage::disk('local')->put('ebooks/e.pdf', '%PDF-1.4');
        $book = Book::factory()->create();
        $book->forceFill(['excerpts' => [['label' => 'Chapitre 1', 'path' => 'excerpts/x.pdf', 'size' => 8]], 'ebook_path' => 'ebooks/e.pdf'])->save();
        expect($this->get('/books/'.$book->slug.'/extraits/0')->assertOk()->headers->get('Content-Disposition'))->toStartWith('attachment;');
        expect($this->get('/books/'.$book->slug.'/livre-electronique')->assertOk()->headers->get('Content-Disposition'))->toStartWith('inline;');
        $book->forceFill(['status' => ContentStatus::Draft])->save();
        $this->get('/books/'.$book->slug.'/extraits/0')->assertNotFound();
        $this->get('/books/'.$book->slug.'/livre-electronique')->assertNotFound();
    });

    it('refuse un chemin d\'extrait hors du dossier prévu', function () {
        Storage::fake('local');
        Storage::disk('local')->put('.env', 'SECRET');
        $book = Book::factory()->create();
        $book->forceFill(['excerpts' => [['label' => 'x', 'path' => 'excerpts/../.env', 'size' => 1]]])->save();
        $this->get('/books/'.$book->slug.'/extraits/0')->assertNotFound();
    });

    it('apparaît dans le sitemap et la recherche globale', function () {
        $book = Book::factory()->create(['title' => 'Introuvable ailleurs']);
        $this->get('/sitemap.xml')->assertSee($book->publicUrl(), false);
        $this->getJson('/recherche?q=Introuvable')->assertJsonPath('results.0.url', $book->publicUrl());
    });
});
