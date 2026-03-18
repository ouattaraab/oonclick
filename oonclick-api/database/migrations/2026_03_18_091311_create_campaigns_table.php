<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertiser_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('format', ['video', 'scratch', 'quiz', 'flash'])->default('video');
            $table->enum('status', [
                'draft',
                'pending_review',
                'approved',
                'active',
                'paused',
                'completed',
                'rejected',
                'cancelled',
            ])->default('draft');
            $table->unsignedInteger('budget');
            $table->unsignedInteger('cost_per_view')->default(100);
            $table->unsignedInteger('max_views');
            $table->unsignedInteger('views_count')->default(0);
            $table->string('media_url')->nullable();
            $table->string('media_path')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->json('targeting')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('advertiser_id');
            $table->index('status');
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
