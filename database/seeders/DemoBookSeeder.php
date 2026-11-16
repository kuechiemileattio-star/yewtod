<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/** Livres de DÉMONSTRATION (cahier § 12.4) : refusé hors environnement local. */
class DemoBookSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoBookSeeder est réservé à l\'environnement local.');
        }
        if (Book::query()->exists()) {
            return;
        }
        $editor = User::query()->where('email', 'editeur@yewtod.test')->first()?->id;
        $ebook = DemoContentSeeder::demoPdf('ebooks');
        $excerpt = DemoContentSeeder::demoPdf('excerpts');

        $books = [
            ['La société des égaux', 'Pierre Rosanvallon', 'Seuil', 2011, 'politique', 'intermediaire'],
            ['Le capital au XXIe siècle', 'Thomas Piketty', 'Seuil', 2013, 'economie', 'exigeant'],
            ['La misère du monde', 'Pierre Bourdieu (dir.)', 'Seuil', 1993, 'sciences-sociales', 'accessible'],
            ['Sapiens', 'Yuval Noah Harari', 'Albin Michel', 2015, 'histoire', 'accessible'],
            ['L\'âge du capitalisme de surveillance', 'Shoshana Zuboff', 'Zulma', 2020, 'ia', 'intermediaire'],
            ['Le hasard et la nécessité', 'Jacques Monod', 'Seuil', 1970, 'philosophie', 'exigeant'],
            ['Retour à Reims', 'Didier Eribon', 'Fayard', 2009, 'sciences-sociales', 'accessible'],
            ['Économie du bien commun', 'Jean Tirole', 'PUF', 2016, 'economie', 'intermediaire'],
            ['Les décisions absurdes', 'Christian Morel', 'Gallimard', 2002, 'entrepreneuriat', 'accessible'],
        ];
        $created = [];
        foreach ($books as $i => [$title, $author, $publisher, $year, $category, $difficulty]) {
            $book = new Book([
                'title' => $title, 'author' => $author, 'publisher' => $publisher, 'year' => $year, 'month' => ($i % 12) + 1,
                'category' => $category, 'difficulty' => $difficulty, 'pages' => 200 + $i * 47,
                'summary' => fake('fr_FR')->paragraph(3),
                'review' => 'Un ouvrage de démonstration : '.fake('fr_FR')->paragraph(5),
                'quotes' => [fake('fr_FR')->sentence(14), fake('fr_FR')->sentence(10)],
                'isbn_print' => '978-2-02-'.str_pad((string) (100000 + $i * 731), 6, '0').'-'.($i % 10),
                'price_print' => (string) (14.9 + $i * 2), 'price_ebook' => $i % 3 === 0 ? null : (string) (7.99 + $i),
                'buy_print_url' => $i % 4 === 3 ? null : 'https://www.placedeslibraires.fr/',
                'description' => fake('fr_FR')->paragraphs(3, true),
                'author_bio' => fake('fr_FR')->paragraph(3),
                'composition' => [['title' => 'Introduction', 'page' => 1], ['title' => 'Première partie', 'page' => 23], ['title' => 'Deuxième partie', 'page' => 98], ['title' => 'Conclusion', 'page' => 180]],
            ]);
            $book->forceFill([
                'slug' => Str::slug($title),
                'status' => ContentStatus::Published,
                'published_at' => now()->subDays(3 + $i * 6),
                'created_by' => $editor,
                'cover_path' => DemoContentSeeder::cover($title, 'books', 800, 1200),
                'cover_alt' => 'Couverture de '.$title,
                'ebook_path' => $i === 0 ? $ebook['path'] : null,
                'ebook_size' => $i === 0 ? $ebook['size'] : null,
                'ebook_description' => $i === 0 ? 'Version numérique au format PDF, lisible en ligne.' : null,
                'excerpts' => $i < 2 ? [['label' => 'Chapitre 1', 'path' => $excerpt['path'], 'size' => $excerpt['size']]] : [],
                'views_count' => random_int(10, 400),
            ])->save();
            $created[] = $book;
        }
        $created[0]->related()->sync([$created[1]->id => ['position' => 0], $created[6]->id => ['position' => 1]]);

        $draft = new Book(['title' => 'Brouillon : un livre à venir', 'author' => 'Auteur inconnu', 'category' => 'histoire', 'review' => 'À rédiger.']);
        $draft->forceFill(['slug' => 'brouillon-un-livre-a-venir', 'status' => ContentStatus::Draft, 'created_by' => $editor])->save();
    }
}
