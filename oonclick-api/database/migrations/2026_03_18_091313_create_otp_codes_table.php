<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('code', 255); // bcrypt hash du code OTP
            $table->enum('type', ['registration', 'login', 'withdrawal', 'kyc'])->default('registration');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index('phone');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
