<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change format from enum to string to allow dynamic formats
        DB::statement("ALTER TABLE campaigns MODIFY format VARCHAR(50) NOT NULL DEFAULT 'video'");

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('end_mode', 20)->default('target_reached')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('end_mode');
        });
        DB::statement("ALTER TABLE campaigns MODIFY format ENUM('video','scratch','quiz','flash') NOT NULL DEFAULT 'video'");
    }
};
