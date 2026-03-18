<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_fingerprint_id')->nullable()->constrained('device_fingerprints')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('watch_duration_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_credited')->default(false);
            $table->timestamp('credited_at')->nullable();
            $table->unsignedInteger('amount_credited')->default(0);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('subscriber_id');
            $table->index('is_credited');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
    }
};
