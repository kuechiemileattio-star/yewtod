<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Livres (cahier § 3.1, décision 5) : table à part car leur logique diffère
 * (prix, achat, avis). Prix en EUR (decimal, jamais float), contraintes
 * CHECK sur la catégorie, la difficulté, le mois, les prix et le statut.
 * Le PDF de l'ebook et les extraits sont sur le disque PRIVÉ.
 */
return new class extends Migration
{
    public const CATEGORIES = "'ia','economie','politique','physique','mathematiques','philosophie','entrepreneuriat','histoire','sciences-sociales'";

    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            // Identité
            $table->string('title', 300);
            $table->string('slug', 320)->unique();
            $table->string('author', 200);
            $table->string('publisher', 200)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->string('category', 30);
            $table->string('difficulty', 20)->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('doi', 200)->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt', 300)->nullable();
            // Avis Yewtod
            $table->text('summary')->nullable();
            $table->text('review');
            $table->json('quotes');
            $table->string('external_url', 2000)->nullable();
            // Vente (EUR)
            $table->string('isbn_print', 20)->nullable();
            $table->string('isbn_ebook', 20)->nullable();
            $table->decimal('price_print', 8, 2)->nullable();
            $table->decimal('price_ebook', 8, 2)->nullable();
            $table->string('buy_print_url', 2000)->nullable();
            $table->string('buy_ebook_url', 2000)->nullable();
            // Version électronique
            $table->string('ebook_path')->nullable();
            $table->unsignedBigInteger('ebook_size')->nullable();
            $table->text('ebook_description')->nullable();
            $table->json('composition');
            $table->json('excerpts');
            // Fiche longue
            $table->text('description')->nullable();
            $table->text('author_bio')->nullable();
            // Publication
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index('category');
        });

        DB::statement('ALTER TABLE books ADD CONSTRAINT books_category_check CHECK (category IN ('.self::CATEGORIES.'))');
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_difficulty_check CHECK (difficulty IS NULL OR difficulty IN ('accessible','intermediaire','exigeant'))");
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_status_check CHECK (status IN ('draft','review','scheduled','published','archived'))");
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_scheduled_check CHECK (status <> 'scheduled' OR scheduled_at IS NOT NULL)");
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_month_check CHECK (month IS NULL OR month BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_price_check CHECK ((price_print IS NULL OR price_print >= 0) AND (price_ebook IS NULL OR price_ebook >= 0))');

        // « Ouvrages similaires » par identifiant (bug n° 9).
        Schema::create('book_related', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->primary(['book_id', 'related_id']);
        });
        DB::statement('ALTER TABLE book_related ADD CONSTRAINT book_related_self_check CHECK (book_id <> related_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('book_related');
        Schema::dropIfExists('books');
    }
};
