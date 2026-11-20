<?php

use App\Livewire\Dashboard\ContentEditor;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

// Cahier § 8.4 (fichiers), § 3 (CSV). L'analyse IA a été retirée (D71).

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

it('ne propose plus aucune analyse IA (fonction retirée, D71)', function () {
    $ed = member('editeur');
    Livewire::actingAs($ed)->test(ContentEditor::class, ['type' => 'rapports'])->assertDontSee('Analyse IA')->assertDontSee('Gemini');
    expect(Schema::hasTable('ai_analyses'))->toBeFalse()
        ->and(config('services.gemini'))->toBeNull()
        ->and(method_exists(ContentEditor::class, 'analyzeWithAi'))->toBeFalse();
});
