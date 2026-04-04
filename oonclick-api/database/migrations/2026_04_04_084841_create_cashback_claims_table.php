<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('cashback_claims')) {
            return;
        }

        Schema::create('cashback_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained('partner_offers')->cascadeOnDelete();
            $table->unsignedInteger('purchase_amount');
            $table->unsignedInteger('cashback_amount');
            $table->string('receipt_reference')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, credited
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_claims');
    }
};
