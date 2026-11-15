<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Contenus éditables du site (cahier § 4.8, 4.9, 5.9, bug n° 2 et n° 7) :
 * réglages clé/valeur, pages éditables (clés cohérentes : meet, home, footer),
 * réseaux sociaux ordonnés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('value');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('content');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 40);
            $table->string('url', 500);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('settings');
    }
};
