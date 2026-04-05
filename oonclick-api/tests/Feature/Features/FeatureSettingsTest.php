<?php

use App\Models\FeatureSetting;

describe('Feature Settings API', function () {

    beforeEach(function () {
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'streak'],
            ['label' => 'Streak', 'is_enabled' => true, 'config' => ['bonus_schedule' => [50]], 'sort_order' => 1]
        );
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'levels'],
            ['label' => 'Niveaux', 'is_enabled' => false, 'config' => [], 'sort_order' => 2]
        );
    });

    it('retourne uniquement les features activees', function () {
        $response = $this->getJson('/api/config/features');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.slug', 'streak');
    });

    it('cache les resultats', function () {
        FeatureSetting::clearCache();

        $result1 = FeatureSetting::isEnabled('streak');
        $result2 = FeatureSetting::isEnabled('streak');

        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
    });

    it('retourne la config correcte', function () {
        $config = FeatureSetting::getConfig('streak');

        expect($config)->toBeArray();
        expect($config['bonus_schedule'])->toBe([50]);
    });
});
