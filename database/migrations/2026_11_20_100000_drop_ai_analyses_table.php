<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Décision D71 : l'analyse IA des PDF (Gemini) est retirée du projet.
 * La migration d'origine (2026_11_15_100700) est déjà commitée et ne se
 * modifie plus : la table est supprimée ici. Le retour arrière la recrée
 * à l'identique (vide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_analyses');
    }

    public function down(): void
    {
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
};
