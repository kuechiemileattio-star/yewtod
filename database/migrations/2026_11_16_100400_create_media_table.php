<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Médiathèque (cahier § 10 P2, bug n° 6) : fichiers PUBLICS réutilisables
 * (images converties en WebP, PDF). 20 Mo maximum (cahier § 8.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->string('kind', 10);
            $table->string('original_name', 200);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('alt', 300)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });
        DB::statement("ALTER TABLE media ADD CONSTRAINT media_kind_check CHECK (kind IN ('image','document'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
