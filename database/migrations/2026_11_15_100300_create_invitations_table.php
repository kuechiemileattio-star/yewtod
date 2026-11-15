<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Invitations (cahier § 5.7) : jeton stocké HACHÉ (SHA-256), jamais en clair.
 * Une fuite de la base ne permet donc pas d'activer un compte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email', 254);
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
        });

        DB::statement("ALTER TABLE invitations ADD CONSTRAINT invitations_status_check CHECK (status IN ('pending','accepted','expired','revoked'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
