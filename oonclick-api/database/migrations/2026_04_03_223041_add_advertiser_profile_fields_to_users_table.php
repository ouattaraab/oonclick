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
            // Advertiser type: 'individual' (personne physique) or 'company' (société)
            $table->string('advertiser_type', 20)->nullable()->after('address');

            // Individual-specific fields
            $table->date('birth_date')->nullable()->after('advertiser_type');
            $table->string('city', 100)->nullable()->after('birth_date');
            $table->string('id_number', 50)->nullable()->after('city');

            // Company-specific fields
            $table->string('company_size', 20)->nullable()->after('id_number');

            // Campaign preferences (step 3 — optional)
            $table->string('monthly_budget', 50)->nullable()->after('company_size');
            $table->json('target_sectors')->nullable()->after('monthly_budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'advertiser_type',
                'birth_date',
                'city',
                'id_number',
                'company_size',
                'monthly_budget',
                'target_sectors',
            ]);
        });
    }
};
