<?php

namespace Tests;

use Database\Seeders\PlatformConfigSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup global pour chaque test.
     * Insère les PlatformConfig si la table existe (évite les erreurs
     * sur les tests sans RefreshDatabase).
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Seeder les configs plateforme uniquement si la table a été migrée
        if (Schema::hasTable('platform_configs')) {
            $this->seed(PlatformConfigSeeder::class);
        }
    }
}
