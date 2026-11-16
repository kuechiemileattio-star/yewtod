<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\User;
use App\Services\ContentWorkflow;
use App\Services\ImageProcessor;
use App\Services\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Publications de DÉMONSTRATION (cahier § 12.4) : les 8 types, avec leurs
 * champs riches, quelques brouillons et un contenu programmé. Refusé hors
 * environnement local. Les couvertures sont dessinées par GD (aucune image
 * téléchargée), le PDF de démonstration est écrit à la main.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoContentSeeder est réservé à l\'environnement local.');
        }
        if (Content::query()->exists()) {
            return;
        }

        $editor = User::query()->where('email', 'editeur@yewtod.test')->first();
        $contrib = User::query()->where('email', 'contributeur@yewtod.test')->first();
        $pdf = $this->demoPdf();
        $para = fn (int $n = 3) => collect(range(1, $n))->map(fn () => '<p>'.fake('fr_FR')->paragraph(6).'</p>')->implode('');

        $articles = [
            ['Ce que la sociologie dit vraiment du télétravail', 'economie', 'dossier', true],
            ['Les classes moyennes face à l\'inflation : un décrochage silencieux', 'societe', 'actualite', true],
            ['Qu\'est-ce que la « reproduction sociale » ?', 'education', 'definition', false],
            ['Réseaux sociaux et polarisation : ce que montrent les données', 'numerique', 'dossier', true],
            ['Brève : la participation électorale des 18-24 ans', 'politique', 'breve', false],
            ['Territoires ruraux : l\'accès aux soins en question', 'sante', 'dossier', false],
        ];
        foreach ($articles as $i => [$title, $theme, $kind, $featured]) {
            $this->make(ContentType::Article, $title, [
                'summary' => fake('fr_FR')->paragraph(4),
                'themes' => [$theme], 'is_featured' => $featured,
                'tags' => array_slice(['enquête', 'inégalités', 'travail', 'données', 'territoires', 'jeunesse'], $i % 3, 3),
                'sections' => [
                    ['title' => 'Introduction', 'content' => $para(2)],
                    ['title' => 'Ce que disent les enquêtes', 'content' => $para(3).'<ul><li>Premier constat</li><li>Second constat</li></ul>'],
                    ['title' => 'Limites et débats', 'content' => $para(2).'<blockquote>Une citation éclairante issue d\'un entretien de terrain.</blockquote>'],
                    ['title' => 'Conclusion', 'content' => $para(1)],
                ],
                'references' => [['title' => 'Insee, Enquête Emploi', 'url' => 'https://www.insee.fr'], ['title' => 'Bourdieu P., La Distinction, 1979', 'url' => null]],
                'meta' => ['subtitle' => 'Enquête, chiffres et controverses', 'content_kind' => $kind],
            ], $i < 4 ? $editor : $contrib, daysAgo: 3 + $i * 9, cover: $i % 2 === 0, file: $i === 0 ? $pdf : null);
        }

        $this->make(ContentType::Report, 'Rapport 2026 sur la précarité étudiante', [
            'summary' => 'Ce rapport synthétise trois années d\'enquête auprès de 2 400 étudiants : logement, alimentation, santé et réussite.',
            'themes' => ['education'], 'tags' => ['étudiants', 'précarité', 'logement'],
            'sections' => [['title' => 'Méthodologie', 'content' => $para(2)], ['title' => 'Résultats', 'content' => $para(3)], ['title' => 'Recommandations', 'content' => '<ol><li>Renforcer les aides au logement</li><li>Étendre la restauration à tarif social</li></ol>']],
            'meta' => ['authors' => ['A. Diallo', 'M. Laurent'], 'version' => '1.2', 'doi' => '10.5281/zenodo.1234567', 'volume' => '3', 'issue' => '2', 'affiliation' => 'Yewtod SS — pôle Enquêtes', 'pages' => 48,
                'toc' => [['title' => 'Introduction', 'page' => 3, 'summary' => 'Contexte et objectifs de l\'enquête.'], ['title' => 'Méthodologie', 'page' => 7], ['title' => 'Résultats', 'page' => 15, 'summary' => 'Logement, alimentation, santé.'], ['title' => 'Recommandations', 'page' => 41]]],
        ], $editor, daysAgo: 6, cover: true, file: $pdf);
        $this->make(ContentType::Report, 'Note de conjoncture : l\'emploi des seniors', ['summary' => 'Panorama de l\'emploi après 55 ans.', 'themes' => ['economie'], 'meta' => ['authors' => ['Yewtod SS']]], $editor, daysAgo: 40);

        $this->make(ContentType::Study, 'Étude : l\'engagement associatif après la pandémie', [
            'summary' => 'Une étude par questionnaire et entretiens menée dans trois villes moyennes.', 'themes' => ['societe'],
            'sections' => [['title' => 'Objectifs', 'content' => $para(1)], ['title' => 'Résultats', 'content' => $para(2)]],
            'meta' => ['authors' => ['C. Martin'], 'research_question' => 'Les bénévoles sont-ils revenus dans les associations après 2021, et sous quelles formes ?'],
        ], $editor, daysAgo: 12);
        $this->make(ContentType::ResearchNote, 'Note : piste sur les tiers-lieux ruraux', [
            'summary' => 'Les tiers-lieux pourraient jouer un rôle de « services publics informels » dans les zones peu denses.', 'themes' => ['environnement'],
            'sections' => [['title' => 'Observations', 'content' => "Premières visites de terrain\n• trois lieux visités\n• des usages très variés\nLes publics rencontrés sont surtout des actifs indépendants et des retraités, ce qui interroge la notion d'ouverture."]],
            'meta' => ['progress' => 'en_cours', 'links' => [['label' => 'France Tiers-Lieux', 'url' => 'https://francetierslieux.fr']]],
        ], $contrib, daysAgo: 15);
        $this->make(ContentType::Experiment, 'Expérimentation : mesurer la ségrégation scolaire avec des données ouvertes', [
            'summary' => 'Reproduire un indice de ségrégation à partir des IPS publiés par le ministère.', 'themes' => ['education'],
            'sections' => [['title' => 'Protocole', 'content' => $para(2)], ['title' => 'Résultats', 'content' => $para(1)]],
            'meta' => ['source_code_url' => 'https://github.com/'],
        ], $editor, daysAgo: 20);

        $chart = fn (string $type, string $title) => ['title' => $title, 'description' => 'Données de démonstration.', 'chart_type' => $type, 'data' => [
            'labels' => ['2019', '2020', '2021', '2022', '2023', '2024'],
            'series' => [['name' => 'Hommes', 'values' => [42, 38, 45, 47, 49, 52]], ['name' => 'Femmes', 'values' => [35, 33, 40, 44, 48, 51]]],
        ]];
        $this->make(ContentType::DataVisualization, 'Visualisation : l\'évolution du taux d\'emploi par sexe', [
            'summary' => 'Six façons de représenter la même série, pour comparer les lectures.', 'themes' => ['economie'],
            'meta' => ['data_source' => 'Données fictives de démonstration', 'legend' => 'Taux en pourcentage de la population de 15 à 64 ans.', 'analysis' => 'L\'écart se resserre sur la période.',
                'blocks' => [$chart('bar', 'Barres groupées'), $chart('line', 'Lignes'), $chart('area', 'Aires'), $chart('stacked', 'Barres empilées'), $chart('scatter', 'Nuage de points'),
                    ['title' => 'Répartition des répondants', 'chart_type' => 'pie', 'data' => ['labels' => ['Salariés', 'Indépendants', 'Sans emploi', 'Étudiants'], 'series' => [['name' => 'Part', 'values' => [54, 12, 9, 25]]]]]]],
        ], $editor, daysAgo: 9, cover: true);

        $series = $this->make(ContentType::DocumentarySeries, 'Série : Vivre de la terre', [
            'summary' => 'Une série documentaire en trois épisodes sur les nouveaux agriculteurs.', 'themes' => ['environnement'],
            'meta' => ['trailer_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'theme_label' => 'Agriculture et transitions'],
        ], $editor, daysAgo: 30, cover: true);
        foreach (['S\'installer', 'Tenir', 'Transmettre'] as $n => $title) {
            $this->make(ContentType::DocumentaryEpisode, 'Épisode '.($n + 1).' : '.$title, [
                'summary' => fake('fr_FR')->sentence(18), 'parent_id' => $series->id,
                'meta' => ['video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'chapters' => [['time' => '0:00', 'title' => 'Ouverture'], ['time' => '4:12', 'title' => 'Sur le terrain'], ['time' => '11:30', 'title' => 'Témoignages']]],
            ], $editor, daysAgo: 28 - $n * 5);
        }

        // Quelques contenus NON visibles : brouillon, relecture, programmé futur.
        $this->make(ContentType::Article, 'Brouillon : notes sur le logement social', ['summary' => 'Brouillon de démonstration.'], $contrib, status: ContentStatus::Draft);
        $this->make(ContentType::Article, 'En relecture : la jeunesse rurale', ['summary' => 'Soumis par un contributeur.'], $contrib, status: ContentStatus::Review);
        $this->make(ContentType::Study, 'Programmée : enquête sur les mobilités', ['summary' => 'Publication programmée.'], $editor, status: ContentStatus::Scheduled);

        app(SiteSettings::class)->set('reflection', ['text' => 'Le savoir ne vaut que s\'il circule : publier en accès libre, c\'est rendre la recherche à ceux qu\'elle étudie.', 'author' => 'Yewtod SS', 'date' => now()->toDateString()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{path: string, size: int}|null  $file
     */
    private function make(ContentType $type, string $title, array $data, ?User $author, int $daysAgo = 1, bool $cover = false, ?array $file = null, ContentStatus $status = ContentStatus::Published): Content
    {
        $content = new Content(collect($data)->except(['parent_id'])->all() + ['title' => $title]);
        $content->forceFill([
            'type' => $type,
            'slug' => Str::slug($title),
            'author_id' => $author?->id,
            'status' => $status,
            'published_at' => $status === ContentStatus::Published ? now()->subDays($daysAgo)->setTime(9, 30) : null,
            'scheduled_at' => $status === ContentStatus::Scheduled ? now()->addDays(5) : null,
            'parent_id' => $data['parent_id'] ?? null,
            'episode_number' => isset($data['parent_id']) ? Content::query()->where('parent_id', $data['parent_id'])->count() + 1 : null,
            'views_count' => random_int(20, 900),
            'downloads_count' => $file ? random_int(5, 120) : 0,
        ]);
        if ($cover) {
            $content->forceFill(['cover_path' => $this->cover($title), 'cover_alt' => 'Illustration : '.$title]);
        }
        if ($file) {
            $content->forceFill(['file_path' => $file['path'], 'file_name' => 'demo.pdf', 'file_size' => $file['size']]);
        }
        $content->forceFill(['reading_time' => ContentWorkflow::readingTime($content)]);
        $content->save();

        return $content;
    }

    /** Couverture abstraite dessinée par GD puis passée par ImageProcessor (WebP + srcset). */
    private function cover(string $seed): string
    {
        $w = 1600;
        $h = 900;
        $img = imagecreatetruecolor($w, $h);
        $palettes = [[[23, 34, 56], [240, 100, 77]], [[46, 145, 161], [23, 34, 56]], [[245, 185, 77], [240, 100, 77]], [[23, 34, 56], [46, 145, 161]]];
        [$a, $b] = $palettes[crc32($seed) % 4];
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $col = imagecolorallocate($img, (int) ($a[0] + ($b[0] - $a[0]) * $t), (int) ($a[1] + ($b[1] - $a[1]) * $t), (int) ($a[2] + ($b[2] - $a[2]) * $t));
            imageline($img, 0, $y, $w, $y, $col);
        }
        mt_srand(crc32($seed));
        for ($i = 0; $i < 7; $i++) {
            $c = imagecolorallocatealpha($img, 247, 243, 236, mt_rand(70, 110));
            imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), $r = mt_rand(120, 520), $r, $c);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'cov').'.png';
        imagepng($img, $tmp);
        imagedestroy($img);
        $stored = app(ImageProcessor::class)->store(new UploadedFile($tmp, 'cover.png', 'image/png', null, true), 'covers');
        @unlink($tmp);

        return $stored['path'];
    }

    /** @return array{path: string, size: int} PDF minimal valide sur le disque privé. */
    private function demoPdf(): array
    {
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length 58>>stream\nBT /F1 24 Tf 72 760 Td (Yewtod SS - document de demo) Tj ET\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
        $path = 'documents/demo-'.Str::random(20).'.pdf';
        Storage::disk('local')->put($path, $pdf);

        return ['path' => $path, 'size' => strlen($pdf)];
    }
}
