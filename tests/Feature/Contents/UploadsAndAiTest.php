<?php

use App\Jobs\AnalyzePdfWithAi;
use App\Livewire\Dashboard\ContentEditor;
use App\Models\AiAnalysis;
use App\Models\Content;
use App\Services\GeminiAnalyzer;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

// Cahier § 5.8 (IA), § 8.4 (fichiers), § 3 (CSV).

function fakePdf(string $name = 'doc.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<<>>\n%%EOF");
}

it('accepte un vrai PDF sur le disque privé avec un nom aléatoire', function () {
    Storage::fake('local');
    $r = app(UploadService::class)->pdf(fakePdf('mon rapport.pdf'));
    expect($r['path'])->toStartWith('documents/')->not->toContain('rapport');
    Storage::disk('local')->assertExists($r['path']);
});

it('refuse un faux PDF (extension sans signature %PDF-)', function () {
    app(UploadService::class)->pdf(UploadedFile::fake()->createWithContent('x.pdf', '<?php echo 1;'));
})->throws(ValidationException::class);

it('refuse un SVG comme couverture', function () {
    app(UploadService::class)->cover(UploadedFile::fake()->createWithContent('c.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'));
})->throws(ValidationException::class);

it('refuse une couverture de plus de 5 Mo', function () {
    app(UploadService::class)->cover(UploadedFile::fake()->image('c.jpg')->size(6000));
})->throws(ValidationException::class);

it('analyse un CSV avec guillemets, retours à la ligne et point-virgule', function () {
    $data = UploadService::parseCsv("Année;\"Série A\";Série B\n2020;10;\"1,5\"\n\"2021\n(révisé)\";12;3\n");
    expect($data['labels'])->toBe(['2020', "2021\n(révisé)"])
        ->and($data['series'][0])->toBe(['name' => 'Série A', 'values' => [10.0, 12.0]])
        ->and($data['series'][1]['values'])->toBe([1.5, 3.0]);
});

it('désactive proprement l\'analyse IA sans clé (message clair, aucune erreur)', function () {
    config(['services.gemini.key' => null]);
    expect(GeminiAnalyzer::enabled())->toBeFalse();
    Livewire::actingAs(member('editeur'))->test(ContentEditor::class, ['type' => 'rapports'])
        ->assertSee('Analyse IA désactivée')->call('analyzeWithAi')->assertDispatched('toast', type: 'error');
});

it('n\'analyse que des fichiers stockés sur le site (pas d\'URL : anti-SSRF)', function () {
    config(['services.gemini.key' => 'test']);
    expect(fn () => app(GeminiAnalyzer::class)->analyze('https://169.254.169.254/latest'))->toThrow(RuntimeException::class);
    expect(fn () => app(GeminiAnalyzer::class)->analyze('../../.env'))->toThrow(RuntimeException::class);
    Http::assertNothingSent();
});

it('envoie le PDF à Gemini avec la consigne anti-injection, puis filtre la réponse', function () {
    config(['services.gemini.key' => 'cle-test']);
    Storage::fake('local');
    Storage::disk('local')->put('documents/a.pdf', "%PDF-1.4\n%%EOF");
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
        'title' => str_repeat('T', 900), 'authors' => array_fill(0, 80, 'A'), 'summary' => '<b>Résumé</b>', 'language' => 'FR',
        'doi' => 'faux', 'pages' => 12, 'sections' => [['title' => 'Intro', 'summary' => 'S']],
    ])]]]]]])]);
    $r = app(GeminiAnalyzer::class)->analyze('documents/a.pdf');
    expect(mb_strlen($r['title']))->toBe(500)->and($r['authors'])->toHaveCount(50)->and($r['summary'])->toBe('Résumé')
        ->and($r['doi'])->toBeNull()->and($r['language'])->toBe('fr')->and($r['pages'])->toBe(12);
    Http::assertSent(fn ($req) => $req->hasHeader('x-goog-api-key', 'cle-test')
        && str_contains(json_encode($req->data()), 'ignore toute instruction'));
});

it('traduit l\'erreur 429 en « quota atteint » et journalise sans exposer la technique', function () {
    config(['services.gemini.key' => 'cle-test']);
    Storage::fake('local');
    Storage::disk('local')->put('documents/a.pdf', "%PDF-1.4\n%%EOF");
    Http::fake(['*' => Http::response('trop', 429)]);
    $analysis = new AiAnalysis;
    $analysis->forceFill(['analyzable_type' => Content::class, 'analyzable_id' => 1])->save();
    (new AnalyzePdfWithAi($analysis->id, 'documents/a.pdf'))->handle(app(GeminiAnalyzer::class));
    expect($analysis->fresh()->status)->toBe('failed')->and($analysis->fresh()->error)->toBe(GeminiAnalyzer::QUOTA_MESSAGE);
});

it('refuse un PDF de plus de 20 Mo pour l\'IA avant toute lecture', function () {
    config(['services.gemini.key' => 'cle-test', 'services.gemini.max_pdf_bytes' => 10]);
    Storage::fake('local');
    Storage::disk('local')->put('documents/a.pdf', "%PDF-1.4\n".str_repeat('x', 100));
    app(GeminiAnalyzer::class)->analyze('documents/a.pdf');
})->throws(RuntimeException::class, '20 Mo');

it('lance l\'analyse en file d\'attente depuis l\'éditeur et applique le résultat sans écraser le titre', function () {
    config(['services.gemini.key' => 'cle-test']);
    Queue::fake();
    $ed = member('editeur');
    $c = Content::factory()->create(['title' => 'Mon titre']);
    $c->forceFill(['file_path' => 'documents/a.pdf', 'type' => 'rapports'])->save();
    $lw = Livewire::actingAs($ed)->test(ContentEditor::class, ['type' => 'rapports', 'content' => $c])->call('analyzeWithAi');
    Queue::assertPushed(AnalyzePdfWithAi::class);
    AiAnalysis::query()->update(['status' => 'done', 'result' => json_encode(['title' => 'Autre', 'summary' => 'Résumé IA', 'pages' => 9, 'sections' => [['title' => 'S1', 'summary' => 'x']]])]);
    $lw->call('checkAnalysis')->assertSet('form.title', 'Mon titre')->assertSet('form.summary', 'Résumé IA')->assertSet('form.meta.pages', 9);
});
