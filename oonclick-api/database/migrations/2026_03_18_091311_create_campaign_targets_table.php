<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'viewed', 'skipped', 'expired'])->default('pending');
            $table->timestamp('assigned_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'subscriber_id']);
            $table->index('campaign_id');
            $table->index('subscriber_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_targets');
    }
};
