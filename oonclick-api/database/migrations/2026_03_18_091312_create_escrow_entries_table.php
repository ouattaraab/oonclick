<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrow_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_locked');
            $table->unsignedInteger('amount_released')->default(0);
            $table->unsignedInteger('platform_fees_collected')->default(0);
            $table->unsignedInteger('amount_refunded')->default(0);
            $table->string('paystack_reference')->nullable();
            $table->enum('status', ['locked', 'partial', 'released', 'refunded'])->default('locked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_entries');
    }
};
