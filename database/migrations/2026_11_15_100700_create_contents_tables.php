<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Modèle de contenu unifié (cahier § 3.2, décision 1) : une table pour les
 * 8 types, `sections` et `meta` en JSON validés par type côté serveur.
 * Les contraintes CHECK protègent la base même si le code se trompait.
 */
return new class extends Migration
{
    private const TYPES = "'articles','rapports','etudes','notes-de-recherche','series-documentaires','episodes-documentaires','experimentations','visualisations-de-donnees'";

    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('title', 300);
            $table->string('slug', 320);
            $table->text('summary')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt', 300)->nullable();
            // Document principal (PDF…) sur disque PRIVÉ, servi par un contrôleur.
            $table->string('file_path')->nullable();
            $table->string('file_name', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            // « Créé par » / auteur : passe à NULL si le membre est supprimé (bug n° 10).
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // Épisode → série : supprimer une série supprime ses épisodes (cahier § 5.1.7).
            $table->foreignId('parent_id')->nullable()->constrained('contents')->cascadeOnDelete();
            $table->unsignedSmallInteger('episode_number')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('language', 5)->default('fr');
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->unsignedSmallInteger('reading_time')->default(1);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->json('sections');
            $table->json('meta');
            $table->json('references');
            $table->json('tags');
            $table->json('themes');
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->unique(['parent_id', 'episode_number']);
            $table->index(['type', 'status', 'published_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index('is_featured');
        });

        DB::statement('ALTER TABLE contents ADD CONSTRAINT contents_type_check CHECK (type IN ('.self::TYPES.'))');
        DB::statement("ALTER TABLE contents ADD CONSTRAINT contents_status_check CHECK (status IN ('draft','review','scheduled','published','archived'))");
        DB::statement("ALTER TABLE contents ADD CONSTRAINT contents_episode_check CHECK (type <> 'episodes-documentaires' OR (parent_id IS NOT NULL AND episode_number IS NOT NULL AND episode_number > 0))");
        DB::statement("ALTER TABLE contents ADD CONSTRAINT contents_scheduled_check CHECK (status <> 'scheduled' OR scheduled_at IS NOT NULL)");
        DB::statement('ALTER TABLE contents ADD CONSTRAINT contents_reading_check CHECK (reading_time >= 1)');

        // « Contenus similaires » reliés par IDENTIFIANT (bug n° 9), plus par titre.
        Schema::create('content_related', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('contents')->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->primary(['content_id', 'related_id']);
        });
        DB::statement('ALTER TABLE content_related ADD CONSTRAINT content_related_self_check CHECK (content_id <> related_id)');

        // Analyses IA (Gemini) traitées en file d'attente (cahier § 5.8, § 12.1).
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->morphs('analyzable');
            $table->string('status', 20)->default('pending');
            $table->json('result')->nullable();
            $table->string('error', 255)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE ai_analyses ADD CONSTRAINT ai_analyses_status_check CHECK (status IN ('pending','running','done','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
        Schema::dropIfExists('content_related');
        Schema::dropIfExists('contents');
    }
};
