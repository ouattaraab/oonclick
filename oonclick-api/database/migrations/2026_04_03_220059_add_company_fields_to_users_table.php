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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->nullable()->after('avatar_path');
            $table->string('sector', 100)->nullable()->after('company');
            $table->string('rccm', 50)->nullable()->after('sector');
            $table->string('nif', 50)->nullable()->after('rccm');
            $table->string('website')->nullable()->after('nif');
            $table->string('address')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company', 'sector', 'rccm', 'nif', 'website', 'address']);
        });
    }
};
