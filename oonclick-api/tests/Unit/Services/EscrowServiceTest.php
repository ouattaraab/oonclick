<?php

use App\Models\Campaign;
use App\Models\EscrowEntry;
use App\Models\User;
use App\Modules\Payment\Services\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée une campagne en statut draft pour les tests d'escrow.
 */
function createCampaignForEscrow(?int $advertiserId = null): Campaign
{
    $advertiser = $advertiserId
        ? User::find($advertiserId)
        : User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

    return Campaign::create([
        'advertiser_id'    => $advertiser->id,
        'title'            => 'Campagne Test Escrow',
        'format'           => 'video',
        'status'           => 'draft',
        'budget'           => 10000,
        'cost_per_view'    => 100,
        'max_views'        => 100,
        'views_count'      => 0,
        'media_url'        => 'https://cdn.test/video.mp4',
        'duration_seconds' => 30,
    ]);
}

/**
 * Crée un EscrowEntry directement.
 */
function createEscrow(int $campaignId, array $attrs = []): EscrowEntry
{
    return EscrowEntry::create(array_merge([
        'campaign_id'             => $campaignId,
        'amount_locked'           => 10000,
        'amount_released'         => 0,
        'platform_fees_collected' => 0,
        'amount_refunded'         => 0,
        'paystack_reference'      => 'PAY_' . strtoupper(str()->random(16)),
        'status'                  => 'locked',
    ], $attrs));
}

describe('EscrowService', function () {

    it('verrouille le montant en escrow', function () {
        $campaign = createCampaignForEscrow();

        $service = new EscrowService();
        $service->lock($campaign->id, 10000, 'PAY_TEST_001');

        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'             => $campaign->id,
            'amount_locked'           => 10000,
            'amount_released'         => 0,
            'platform_fees_collected' => 0,
            'status'                  => 'locked',
            'paystack_reference'      => 'PAY_TEST_001',
        ]);
    });

    it('refuse un double lock', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, ['status' => 'locked']);

        $service = new EscrowService();

        $this->expectException(\Exception::class);
        $service->lock($campaign->id, 10000, 'PAY_TEST_002');
    });

    it('libère partiellement et passe en statut partial', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, [
            'amount_locked'           => 10000,
            'amount_released'         => 0,
            'platform_fees_collected' => 0,
            'status'                  => 'locked',
        ]);

        $service = new EscrowService();
        $service->release($campaign->id, 60, 40);

        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'             => $campaign->id,
            'amount_released'         => 60,
            'platform_fees_collected' => 40,
            'status'                  => 'partial',
        ]);
    });

    it('passe en statut released quand entièrement consommé', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, [
            'amount_locked'           => 100,
            'amount_released'         => 0,
            'platform_fees_collected' => 0,
            'status'                  => 'locked',
        ]);

        $service = new EscrowService();
        // 60 + 40 = 100 = amount_locked → statut released
        $service->release($campaign->id, 60, 40);

        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'             => $campaign->id,
            'amount_released'         => 60,
            'platform_fees_collected' => 40,
            'status'                  => 'released',
        ]);
    });

    it('rembourse le solde restant', function () {
        $campaign = createCampaignForEscrow();

        // EscrowEntry partial : locked=10000, released=600, fees=400
        createEscrow($campaign->id, [
            'amount_locked'           => 10000,
            'amount_released'         => 600,
            'platform_fees_collected' => 400,
            'amount_refunded'         => 0,
            'status'                  => 'partial',
        ]);

        $service = new EscrowService();
        $result  = $service->refund($campaign->id);

        // 10000 - 600 - 400 = 9000 remboursé
        expect($result['amount'])->toBe(9000);
        expect($result['campaign_id'])->toBe($campaign->id);

        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'     => $campaign->id,
            'amount_refunded' => 9000,
            'status'          => 'refunded',
        ]);
    });

    it('refuse de libérer si l\'escrow est dans un mauvais statut', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, ['status' => 'refunded']);

        $service = new EscrowService();

        $this->expectException(\Exception::class);
        $service->release($campaign->id, 60, 40);
    });

    it('refuse de dépasser le montant verrouillé', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, [
            'amount_locked'           => 100,
            'amount_released'         => 60,
            'platform_fees_collected' => 40,
            'status'                  => 'partial',
        ]);

        $service = new EscrowService();

        // Tentative de libérer 60+40 supplémentaires alors que le budget est épuisé
        $this->expectException(\Exception::class);
        $service->release($campaign->id, 60, 40);
    });

    it('retourne le solde restant', function () {
        $campaign = createCampaignForEscrow();
        createEscrow($campaign->id, [
            'amount_locked'           => 10000,
            'amount_released'         => 600,
            'platform_fees_collected' => 400,
            'amount_refunded'         => 0,
            'status'                  => 'partial',
        ]);

        $service   = new EscrowService();
        $remaining = $service->getRemainingBalance($campaign->id);

        expect($remaining)->toBe(9000);
    });

    it('ne lève pas d\'exception lors du lock si aucun escrow n\'existe', function () {
        $campaign = createCampaignForEscrow();

        $service = new EscrowService();
        // Aucun escrow existant → doit créer sans exception
        $service->lock($campaign->id, 5000, 'PAY_FRESH_001');

        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'        => $campaign->id,
            'amount_locked'      => 5000,
            'status'             => 'locked',
            'paystack_reference' => 'PAY_FRESH_001',
        ]);
    });

});
