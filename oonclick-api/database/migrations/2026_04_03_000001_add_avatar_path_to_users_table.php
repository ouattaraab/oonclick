<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne avatar_path à la table users.
     *
     * Stocke le chemin relatif sur Cloudflare R2 (ex: avatars/42_1712345678.jpg).
     * L'URL publique est reconstituée via le CDN configuré dans filesystems.disks.r2.url.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
