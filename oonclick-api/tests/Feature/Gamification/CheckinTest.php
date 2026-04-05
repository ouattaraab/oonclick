<?php

use App\Models\DailyCheckin;
use App\Models\FeatureSetting;

describe('Daily Checkin & Streak', function () {

    beforeEach(function () {
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'streak'],
            [
                'label'      => 'Streak',
                'is_enabled' => true,
                'config'     => ['bonus_schedule' => [50, 75, 100], 'weekly_bonus' => 500, 'streak_multiplier' => 1.1, 'streak_multiplier_threshold' => 7],
            ]
        );
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'missions'],
            ['label' => 'Missions', 'is_enabled' => false, 'config' => ['missions' => []]]
        );
    });

    it('effectue un check-in et credite le bonus streak', function () {
        $subscriber = makeSubscriber();

        $response = $this->actingAs($subscriber)->postJson('/api/checkin');

        $response->assertStatus(200);
        $response->assertJsonPath('streak_day', 1);
        $response->assertJsonPath('bonus_amount', 50);
        $response->assertJsonPath('streak_enabled', true);

        $this->assertDatabaseHas('daily_checkins', [
            'user_id'    => $subscriber->id,
            'streak_day' => 1,
        ]);
    });

    it('refuse un double check-in le meme jour', function () {
        $subscriber = makeSubscriber();

        $this->actingAs($subscriber)->postJson('/api/checkin');
        $response = $this->actingAs($subscriber)->postJson('/api/checkin');

        $response->assertStatus(409);
    });

    it('retourne le statut du streak', function () {
        $subscriber = makeSubscriber();

        $this->actingAs($subscriber)->postJson('/api/checkin');

        $response = $this->actingAs($subscriber)->getJson('/api/checkin/status');

        $response->assertStatus(200);
        $response->assertJsonPath('checked_in_today', true);
        $response->assertJsonPath('current_streak', 1);
    });

    it('refuse si non authentifie', function () {
        $this->postJson('/api/checkin')->assertStatus(401);
    });
});
