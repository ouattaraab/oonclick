<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : table des factures (US-047).
 *
 * Chaque facture est liée à un annonceur et optionnellement à une campagne.
 * Les montants sont en FCFA (centimes entiers).
 * Le numéro de facture suit le format OON-YYYY-XXXX (séquentiel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained('campaigns')
                ->nullOnDelete();

            // Numéro de facture unique (ex: OON-2026-0001)
            $table->string('invoice_number')->unique();

            $table->enum('type', ['campaign_payment', 'subscription', 'refund'])
                ->default('campaign_payment');

            // Montants en FCFA
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);

            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])
                ->default('draft');

            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();

            // Données supplémentaires (lignes de facturation, adresse, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
