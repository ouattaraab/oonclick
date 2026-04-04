<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : table des check-ins quotidiens (US-049).
 *
 * Chaque ligne représente un check-in journalier d'un abonné.
 * La contrainte unique sur [user_id, checked_in_at] empêche
 * les doublons dans la même journée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checkins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Date du check-in (sans heure, une seule par jour)
            $table->date('checked_in_at')->index();

            // Montant du bonus accordé ce jour (en FCFA)
            $table->unsignedInteger('bonus_amount')->default(10);

            // Numéro du jour dans la série consécutive (1 à N)
            $table->unsignedInteger('streak_day')->default(1);

            $table->timestamps();

            // Un utilisateur ne peut faire qu'un seul check-in par jour
            $table->unique(['user_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checkins');
    }
};
