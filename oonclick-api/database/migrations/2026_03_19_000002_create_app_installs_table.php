<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installs', function (Blueprint $table) {
            $table->id();
            $table->string('install_id', 64)->unique();
            $table->string('platform', 20);
            $table->string('app_version', 20)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('launch_count')->default(1);

            $table->index('platform');
            $table->index('app_version');
            $table->index('first_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installs');
    }
};
