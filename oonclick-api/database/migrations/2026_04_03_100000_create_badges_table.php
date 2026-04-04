<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : table des badges et table pivot user_badges (US-050).
 *
 * Chaque badge correspond à un niveau ou une récompense thématique.
 * La table pivot user_badges enregistre la date d'obtention.
 * La colonne xp_points est ajoutée à users pour le calcul de niveau.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table des badges disponibles
        Schema::create('badges', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description');
            $table->string('icon');

            // Points XP requis pour débloquer ce badge
            $table->unsignedInteger('xp_required')->default(0);

            // Niveau gamification (1 à 5, 0 = badge spécial)
            $table->unsignedTinyInteger('level')->default(0);

            // Catégorie du badge
            $table->enum('category', ['views', 'referral', 'streak', 'spending', 'special'])
                ->default('special');

            $table->timestamps();
        });

        // Table pivot : badges gagnés par les utilisateurs
        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('badge_id')
                ->constrained('badges')
                ->cascadeOnDelete();

            $table->timestamp('earned_at')->useCurrent();

            $table->timestamps();

            // Un utilisateur ne peut pas gagner le même badge deux fois
            $table->unique(['user_id', 'badge_id']);
        });

        // Ajouter xp_points à la table users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_points')->default(0)->after('trust_score');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('xp_points');
        });

        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
