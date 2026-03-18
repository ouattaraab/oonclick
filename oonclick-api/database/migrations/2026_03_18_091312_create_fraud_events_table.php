<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'rapid_views',
                'multiple_accounts',
                'vpn_detected',
                'bot_behavior',
                'invalid_completion',
                'suspicious_ip',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('trust_score_impact')->default(0);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('severity');
            $table->index('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
    }
};
