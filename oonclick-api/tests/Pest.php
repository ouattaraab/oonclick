<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers globaux
|--------------------------------------------------------------------------
*/

/**
 * Crée un abonné vérifié avec wallet et profil.
 */
function makeSubscriber(array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create(array_merge([
        'role'              => 'subscriber',
        'phone_verified_at' => now(),
        'is_active'         => true,
        'trust_score'       => 100,
        'kyc_level'         => 1,
    ], $attributes));

    \App\Models\Wallet::factory()->create(['user_id' => $user->id]);
    \App\Models\SubscriberProfile::factory()->create(['user_id' => $user->id]);

    return $user;
}

/**
 * Crée un annonceur vérifié avec wallet.
 */
function makeAdvertiser(array $attributes = []): \App\Models\User
{
    $user = \App\Models\User::factory()->create(array_merge([
        'role'              => 'advertiser',
        'phone_verified_at' => now(),
        'is_active'         => true,
        'kyc_level'         => 1,
    ], $attributes));

    \App\Models\Wallet::factory()->create(['user_id' => $user->id]);

    return $user;
}

/**
 * Crée un admin.
 */
function makeAdmin(array $attributes = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge([
        'role'              => 'admin',
        'phone_verified_at' => now(),
        'is_active'         => true,
    ], $attributes));
}

/**
 * Crée une campagne active avec escrow.
 */
function makeActiveCampaign(\App\Models\User $advertiser, array $attributes = []): \App\Models\Campaign
{
    $campaign = \App\Models\Campaign::factory()->create(array_merge([
        'advertiser_id' => $advertiser->id,
        'status'        => 'active',
        'budget'        => 10000,
        'cost_per_view' => 100,
        'max_views'     => 100,
        'views_count'   => 0,
        'format'        => 'video',
        'duration_seconds' => 30,
        'media_url'     => 'https://cdn.oon.click/test/video.mp4',
        'media_path'    => 'campaigns/1/media/test.mp4',
    ], $attributes));

    \App\Models\EscrowEntry::factory()->create([
        'campaign_id'   => $campaign->id,
        'amount_locked' => $campaign->budget,
        'status'        => 'locked',
    ]);

    return $campaign;
}
