<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ContentWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

// Cahier § 5.1, § 5.2, décision 10, bugs n° 1, 3, 5, 9.

function wf(): ContentWorkflow
{
    return app(ContentWorkflow::class);
}

function payload(array $extra = []): array
{
    return array_replace_recursive(['title' => 'Les inégalités territoriales', 'summary' => 'Résumé', 'language' => 'fr',
        'sections' => [['title' => 'Intro', 'content' => '<p>Texte</p>']], 'tags' => 'ville, école', 'themes' => ['societe'], 'meta' => []], $extra);
}

it('bug n° 5 : un Contributeur ne peut pas publier, seulement soumettre en relecture', function () {
    $contrib = member('contributeur');
    expect(fn () => wf()->save($contrib, null, ContentType::Article, payload(), 'publish'))->toThrow(AuthorizationException::class);
    $c = wf()->save($contrib, null, ContentType::Article, payload(), 'review');
    expect($c->status)->toBe(ContentStatus::Review)->and($c->author_id)->toBe($contrib->id);
});

it('un Éditeur publie ; un Contributeur ne modifie plus un contenu publié', function () {
    $contrib = member('contributeur');
    $c = wf()->save($contrib, null, ContentType::Article, payload(), 'review');
    $c = wf()->save(member('editeur'), $c, ContentType::Article, payload(), 'publish');
    expect($c->status)->toBe(ContentStatus::Published)->and($c->published_at)->not->toBeNull();
    expect(fn () => wf()->save($contrib, $c, ContentType::Article, payload(['title' => 'Modifié']), 'review'))->toThrow(AuthorizationException::class);
});

it('un Contributeur ne modifie pas le contenu d\'un autre', function () {
    $c = wf()->save(member('contributeur'), null, ContentType::Article, payload(), 'draft');
    wf()->save(member('contributeur'), $c, ContentType::Article, payload(), 'draft');
})->throws(AuthorizationException::class);

it('génère un slug sans accents, unique par type avec suffixe', function () {
    $ed = member('editeur');
    $a = wf()->save($ed, null, ContentType::Article, payload(['title' => 'Éducation & Société']), 'draft');
    $b = wf()->save($ed, null, ContentType::Article, payload(['title' => 'Éducation & Société']), 'draft');
    $r = wf()->save($ed, null, ContentType::Report, payload(['title' => 'Éducation & Société']), 'draft');
    expect($a->slug)->toBe('education-societe')->and($b->slug)->toBe('education-societe-2')->and($r->slug)->toBe('education-societe');
});

it('bug n° 3 : temps de lecture calculé sur toutes les sections (200 mots/min)', function () {
    $words = implode(' ', array_fill(0, 250, 'mot'));
    $c = wf()->save(member('editeur'), null, ContentType::Article, payload(['summary' => '', 'sections' => [
        ['title' => '', 'content' => "<p>$words</p>"], ['title' => '', 'content' => "<p>$words</p>"],
    ]]), 'draft');
    expect($c->reading_time)->toBe(3);
});

it('ignore les sections vides', function () {
    $c = wf()->save(member('editeur'), null, ContentType::Article, payload(['sections' => [['title' => '', 'content' => '<p> </p>'], ['title' => 'A', 'content' => '']]]), 'draft');
    expect($c->sections)->toHaveCount(1);
});

it('nettoie le HTML des sections (XSS)', function () {
    $c = wf()->save(member('editeur'), null, ContentType::Article, payload(['sections' => [['title' => 'X', 'content' => '<p onclick="alert(1)">ok<script>alert(2)</script><a href="javascript:alert(3)">l</a><img src="https://pistage.test/p.gif"><a href="https://exemple.org">bon</a></p>']]]), 'draft');
    $html = $c->sections[0]['content'];
    expect($html)->not->toContain('onclick')->not->toContain('<script')->not->toContain('javascript:')->not->toContain('<img')
        ->toContain('href="https://exemple.org"')->toContain('noopener');
});

it('bug n° 12 : refuse les liens non http(s) dans les références et métadonnées', function () {
    wf()->save(member('editeur'), null, ContentType::Article, payload(['references' => [['title' => 'R', 'url' => 'javascript:alert(1)']]]), 'draft');
})->throws(ValidationException::class);

it('refuse un DOI invalide et jette les clés meta inconnues', function () {
    $ed = member('editeur');
    expect(fn () => wf()->save($ed, null, ContentType::Report, payload(['meta' => ['doi' => 'pas-un-doi']]), 'draft'))->toThrow(ValidationException::class);
    $c = wf()->save($ed, null, ContentType::Report, payload(['meta' => ['doi' => '10.1234/abc', 'pirate' => 'x', 'authors' => 'A, B']]), 'draft');
    expect($c->meta)->toHaveKey('doi')->not->toHaveKey('pirate')->and($c->meta['authors'])->toBe(['A', 'B']);
});

it('bug n° 1 : un épisode exige une série et reçoit le numéro suivant, unique', function () {
    $ed = member('editeur');
    expect(fn () => wf()->save($ed, null, ContentType::DocumentaryEpisode, payload(['parent_id' => 999999]), 'draft'))->toThrow(ValidationException::class);
    $series = wf()->save($ed, null, ContentType::DocumentarySeries, payload(['title' => 'Série']), 'publish');
    $e1 = wf()->save($ed, null, ContentType::DocumentaryEpisode, payload(['title' => 'E1', 'parent_id' => $series->id]), 'draft');
    $e2 = wf()->save($ed, null, ContentType::DocumentaryEpisode, payload(['title' => 'E2', 'parent_id' => $series->id]), 'draft');
    expect($e1->episode_number)->toBe(1)->and($e2->episode_number)->toBe(2);
    expect(fn () => wf()->save($ed, null, ContentType::DocumentaryEpisode, payload(['title' => 'E3', 'parent_id' => $series->id, 'episode_number' => 2]), 'draft'))->toThrow(ValidationException::class);
});

it('supprimer une série supprime ses épisodes', function () {
    $ed = member('editeur');
    $series = wf()->save($ed, null, ContentType::DocumentarySeries, payload(['title' => 'Série']), 'draft');
    wf()->save($ed, null, ContentType::DocumentaryEpisode, payload(['title' => 'E1', 'parent_id' => $series->id]), 'draft');
    wf()->delete($ed, $series);
    expect(Content::count())->toBe(0);
});

it('programmer exige une date future ; la date de publication prend cette valeur', function () {
    $ed = member('editeur');
    expect(fn () => wf()->save($ed, null, ContentType::Article, payload(), 'schedule'))->toThrow(ValidationException::class);
    expect(fn () => wf()->save($ed, null, ContentType::Article, payload(['scheduled_at' => now()->subDay()->format('Y-m-d H:i')]), 'schedule'))->toThrow(ValidationException::class);
    $when = now()->addDay()->setTimezone('Europe/Paris')->format('Y-m-d H:i');
    $c = wf()->save($ed, null, ContentType::Article, payload(['scheduled_at' => $when]), 'schedule');
    expect($c->status)->toBe(ContentStatus::Scheduled)->and($c->published_at->equalTo($c->scheduled_at))->toBeTrue();
});

it('bug n° 16 : la tâche planifiée publie les contenus programmés échus', function () {
    $c = Content::factory()->status(ContentStatus::Draft)->create();
    $c->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => now()->subMinute()])->save();
    $future = Content::factory()->status(ContentStatus::Draft)->create();
    $future->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => now()->addHour()])->save();
    $this->artisan('yewtod:publish-scheduled')->assertSuccessful();
    expect($c->fresh()->status)->toBe(ContentStatus::Published)->and($future->fresh()->status)->toBe(ContentStatus::Scheduled);
});

it('le scope published() montre publiés et programmés échus, jamais les brouillons', function () {
    Content::factory()->status(ContentStatus::Draft)->create();
    Content::factory()->status(ContentStatus::Review)->create();
    $p = Content::factory()->create();
    $due = Content::factory()->status(ContentStatus::Draft)->create();
    $due->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => now()->subMinute()])->save();
    expect(Content::published()->pluck('id')->sort()->values()->all())->toBe(collect([$p->id, $due->id])->sort()->values()->all());
});

it('bug n° 9 : contenus similaires reliés par identifiant', function () {
    $ed = member('editeur');
    $other = Content::factory()->create();
    $c = wf()->save($ed, null, ContentType::Article, payload(['related' => [$other->id, 999999]]), 'draft');
    $other->forceFill(['title' => 'Titre changé'])->save();
    expect($c->related()->pluck('contents.id')->all())->toBe([$other->id]);
});

it('les champs sensibles ne sont pas assignables en masse', function () {
    foreach (['status', 'published_at', 'author_id', 'parent_id', 'episode_number', 'slug', 'type', 'views_count', 'file_path', 'cover_path'] as $f) {
        expect((new Content)->isFillable($f))->toBeFalse();
    }
});

it('calcule l\'auteur affiché et la référence de citation', function () {
    $c = Content::factory()->type(ContentType::Report)->create(['meta' => ['authors' => ['Awa Diallo']]]);
    expect($c->displayAuthor())->toBe('Awa Diallo')->and($c->citation())->toContain('Awa Diallo ('.$c->displayDate()->year.').')->toEndWith('Rapport, Yewtod SS.');
    $n = Content::factory()->type(ContentType::ResearchNote)->create();
    expect($n->displayAuthor())->toBe('Yewtod SS');
});
