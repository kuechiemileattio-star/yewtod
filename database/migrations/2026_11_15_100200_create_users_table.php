<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Table unique « users » = authentification + profil (cahier § 7).
 * - role_id : restrictOnDelete, on ne peut pas supprimer un rôle encore porté.
 * - invited_by : nullOnDelete (bug n° 10 : supprimer l'inviteur ne bloque plus).
 * - statut borné par une contrainte CHECK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('invited');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('theme_preference', 10)->default('system');

            // Colonnes Fortify : secret TOTP et codes de secours CHIFFRÉS (APP_KEY).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('status');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('invited','active','suspended'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_theme_check CHECK (theme_preference IN ('light','dark','system'))");

        // Jetons de réinitialisation : Laravel les stocke déjà hachés.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 254)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions côté serveur (cahier § 8.3) : permet la page « Sessions actives ».
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Historique des 5 derniers mots de passe (cahier § 8.2).
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password');
            $table->timestamp('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
