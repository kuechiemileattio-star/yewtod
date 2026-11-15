<?php

use App\Enums\ContentType;
use App\Livewire\Dashboard\ContentEditor;
use App\Livewire\Dashboard\ContentIndex;
use App\Models\Content;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

// Cahier § 4.2, § 4.3.

it('ouvre l\'éditeur pour chacun des 8 types', function (ContentType $type) {
    $this->actingAs(member('editeur'))->get(route('dashboard.contents.create', ['type' => $type->value]))->assertOk()->assertSee('Identité');
})->with(ContentType::cases());

it('refuse l\'éditeur aux membres sans manage_articles', function () {
    $this->actingAs(member('moderateur-collaborations'))->get(route('dashboard.contents.create', ['type' => 'articles']))->assertForbidden();
});

it('enregistre un article depuis l\'éditeur, avec un CSV pour une visualisation', function () {
    $ed = member('editeur');
    Livewire::actingAs($ed)->test(ContentEditor::class, ['type' => 'visualisations-de-donnees'])
        ->set('form.title', 'Population')->call('addRow', 'meta.blocks')
        ->set('csv.0', UploadedFile::fake()->createWithContent('d.csv', "Pays,2020\nBénin,12\nFrance,67\n"))
        ->call('save', 'publish')->assertHasNoErrors();
    $c = Content::sole();
    expect($c->status->value)->toBe('published')->and($c->meta['blocks'][0]['data']['labels'])->toBe(['Bénin', 'France']);
});

it('affiche les erreurs de validation dans l\'éditeur', function () {
    Livewire::actingAs(member('editeur'))->test(ContentEditor::class, ['type' => 'articles'])
        ->set('form.title', '')->call('save', 'draft')->assertHasErrors('title');
});

it('un Contributeur voit « Soumettre en relecture » et pas « Publier »', function () {
    Livewire::actingAs(member('contributeur'))->test(ContentEditor::class, ['type' => 'articles'])
        ->assertSee('Soumettre en relecture')->assertDontSee('>Publier<', false);
});

it('réordonne et supprime des sections', function () {
    Livewire::actingAs(member('editeur'))->test(ContentEditor::class, ['type' => 'articles'])
        ->set('form.sections.0.title', 'A')->call('addRow', 'sections')->set('form.sections.1.title', 'B')
        ->call('moveRow', 'sections', 1, -1)->assertSet('form.sections.0.title', 'B')
        ->call('removeRow', 'sections', 0)->assertSet('form.sections.0.title', 'A');
});

it('liste, filtre (liste blanche) et supprime des contenus', function () {
    $ed = member('editeur');
    Content::factory()->count(2)->create();
    $c = Content::factory()->create(['title' => 'À supprimer']);
    Livewire::actingAs($ed)->test(ContentIndex::class, ['type' => 'articles'])
        ->set('sort', 'colonne; DROP TABLE users')->assertOk()
        ->set('search', "' OR 1=1 --")->assertDontSee('À supprimer')
        ->set('search', '')->call('delete', $c->id);
    expect(Content::count())->toBe(2);
});
