<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table kyc_documents pour stocker les pièces justificatives
     * soumises par les utilisateurs dans le cadre de la vérification KYC.
     */
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();

            // Utilisateur concerné
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Niveau KYC visé par ce document (1, 2 ou 3)
            $table->tinyInteger('level')->unsigned();

            // Type de document soumis
            $table->enum('document_type', [
                'national_id',
                'passport',
                'business_reg',
                'selfie',
                'proof_of_address',
            ]);

            // Chemin du fichier sur le disque de stockage
            $table->string('file_path');

            // Disque de stockage (Cloudflare R2 par défaut)
            $table->string('file_disk')->default('r2');

            // Statut du document
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Motif de rejet (nullable, renseigné uniquement si rejeté)
            $table->text('rejection_reason')->nullable();

            // Administrateur ayant traité le document
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Date de révision (approbation ou rejet)
            $table->timestamp('reviewed_at')->nullable();

            // Date de soumission par l'utilisateur
            $table->timestamp('submitted_at');

            $table->timestamps();
        });
    }

    /**
     * Supprime la table kyc_documents.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
