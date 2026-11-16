<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Écritures publiques (cahier § 2.7, § 2.8, § 5.4–5.6, § 7, § 8.6, § 13.1).
 * Tout ce qu'un visiteur peut écrire arrive ici, avec des statuts imposés
 * par le serveur (CHECK), des IP hachées, des jetons hachés et la
 * description des demandes chiffrée par l'application.
 */
return new class extends Migration
{
    public const COLLAB_KINDS = "'recherche','livre','article','intervention','partenariat','interview','etude','generale'";

    public function up(): void
    {
        // Avis polymorphes : publications (articles) et livres (décision 6).
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('reviewable_type', 60);
            $table->unsignedBigInteger('reviewable_id');
            $table->string('name', 80);
            $table->text('body');
            $table->string('status', 10)->default('pending');
            $table->char('ip_hash', 64)->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['reviewable_type', 'reviewable_id', 'status']);
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE reviews ADD CONSTRAINT reviews_status_check CHECK (status IN ('pending','approved','rejected'))");
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_lengths_check CHECK (CHAR_LENGTH(name) BETWEEN 1 AND 80 AND CHAR_LENGTH(body) BETWEEN 1 AND 2000)');

        Schema::create('collaborations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('organization', 200)->nullable();
            $table->string('email', 254);
            $table->string('subject', 200);
            $table->string('kind', 20);
            // Chiffrée par l'application (cast « encrypted ») : texte long car le chiffré est plus grand que le clair.
            $table->text('description');
            $table->string('status', 15)->default('new');
            $table->char('ip_hash', 64)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
        DB::statement('ALTER TABLE collaborations ADD CONSTRAINT collaborations_kind_check CHECK (kind IN ('.self::COLLAB_KINDS.'))');
        DB::statement("ALTER TABLE collaborations ADD CONSTRAINT collaborations_status_check CHECK (status IN ('new','in_progress','archived'))");

        Schema::create('collaboration_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaboration_id')->constrained('collaborations')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('status', 15)->default('pending');
            $table->char('confirm_token_hash', 64)->nullable()->unique();
            $table->timestamp('confirm_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->char('unsubscribe_token_hash', 64)->nullable()->unique();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('brevo_synced_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE newsletter_subscribers ADD CONSTRAINT newsletter_status_check CHECK (status IN ('pending','confirmed','unsubscribed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('collaboration_files');
        Schema::dropIfExists('collaborations');
        Schema::dropIfExists('reviews');
    }
};
