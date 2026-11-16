<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Événements de consultation (cahier § 5.3, § 7) : une ligne par vue ou par
 * téléchargement retenu. Polymorphe pour servir aux publications (lot 05)
 * et aux livres (lot 06). Aucune donnée identifiante : ni IP, ni session.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_events', function (Blueprint $table) {
            $table->id();
            $table->string('eventable_type', 60);
            $table->unsignedBigInteger('eventable_id');
            $table->string('kind', 10);
            $table->timestamp('created_at')->useCurrent();

            // « Les plus consultés sur 7 jours » : filtrage par date puis regroupement.
            $table->index(['eventable_type', 'eventable_id', 'created_at']);
            $table->index(['kind', 'created_at']);
        });

        DB::statement("ALTER TABLE content_events ADD CONSTRAINT content_events_kind_check CHECK (kind IN ('view','download'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('content_events');
    }
};
