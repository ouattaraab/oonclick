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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('reward_amount'); // FCFA
            $table->unsignedInteger('reward_xp')->default(20);
            $table->json('questions'); // [{type:'text'|'radio'|'checkbox', text:'...', options:['A','B','C'], required:true}]
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_responses')->nullable();
            $table->unsignedInteger('responses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->json('targeting')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
