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
        Schema::create('user_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mission_slug', 50);
            $table->date('date');
            $table->unsignedInteger('current_progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'mission_slug', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_missions');
    }
};
