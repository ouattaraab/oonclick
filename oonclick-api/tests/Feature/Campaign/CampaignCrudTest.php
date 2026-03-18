<?php

use App\Models\Campaign;
use App\Models\EscrowEntry;

describe('Gestion des campagnes', function () {

    it('un annonceur peut créer une campagne', function () {
        $advertiser = makeAdvertiser();

        $response = $this->actingAs($advertiser)
            ->postJson('/api/campaigns', [
                'title'  => 'Campagne Test',
                'format' => 'video',
                'budget' => 10000,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Campagne créée avec succès.');

        $this->assertDatabaseHas('campaigns', [
            'advertiser_id' => $advertiser->id,
            'title'         => 'Campagne Test',
            'status'        => 'draft',
            'max_views'     => 100,
        ]);
    });

    it('calcule max_views automatiquement', function () {
        $advertiser = makeAdvertiser();

        $response = $this->actingAs($advertiser)
            ->postJson('/api/campaigns', [
                'title'         => 'Campagne Budget Test',
                'format'        => 'video',
                'budget'        => 5000,
                'cost_per_view' => 100,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('campaigns', [
            'advertiser_id' => $advertiser->id,
            'budget'        => 5000,
            'cost_per_view' => 100,
            'max_views'     => 50,
        ]);
    });

    it('refuse si non annonceur (subscriber)', function () {
        $subscriber = makeSubscriber();

        $response = $this->actingAs($subscriber)
            ->postJson('/api/campaigns', [
                'title'  => 'Campagne Interdite',
                'format' => 'video',
                'budget' => 10000,
            ]);

        $response->assertStatus(403);
    });

    it('refuse de modifier une campagne non-draft', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($advertiser)
            ->patchJson("/api/campaigns/{$campaign->id}", [
                'title' => 'Nouveau titre',
            ]);

        // La campagne active n'est pas en statut draft — le UpdateCampaignRequest
        // ou le controller doit bloquer la modification
        // On vérifie que le titre n'a pas changé en DB
        $this->assertDatabaseMissing('campaigns', [
            'id'    => $campaign->id,
            'title' => 'Nouveau titre',
        ]);
    });

    it('peut soumettre une campagne draft avec média', function () {
        $advertiser = makeAdvertiser();

        $campaign = Campaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status'        => 'draft',
            'media_url'     => 'https://cdn.oon.click/test/video.mp4',
        ]);

        $response = $this->actingAs($advertiser)
            ->postJson("/api/campaigns/{$campaign->id}/submit");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Campagne soumise pour validation.');

        $this->assertDatabaseHas('campaigns', [
            'id'     => $campaign->id,
            'status' => 'pending_review',
        ]);
    });

    it('refuse de soumettre sans média', function () {
        $advertiser = makeAdvertiser();

        $campaign = Campaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status'        => 'draft',
            'media_url'     => null,
        ]);

        $response = $this->actingAs($advertiser)
            ->postJson("/api/campaigns/{$campaign->id}/submit");

        // Doit retourner 422 ou 500 (RuntimeException) selon la gestion des erreurs
        $this->assertContains($response->status(), [422, 500]);

        $this->assertDatabaseHas('campaigns', [
            'id'     => $campaign->id,
            'status' => 'draft',
        ]);
    });

    it('peut mettre en pause une campagne active', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($advertiser)
            ->postJson("/api/campaigns/{$campaign->id}/pause");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Campagne mise en pause.');

        $this->assertDatabaseHas('campaigns', [
            'id'     => $campaign->id,
            'status' => 'paused',
        ]);
    });

    it('refuse de pause si non propriétaire', function () {
        $advertiser1 = makeAdvertiser();
        $advertiser2 = makeAdvertiser();
        $campaign    = makeActiveCampaign($advertiser1);

        $response = $this->actingAs($advertiser2)
            ->postJson("/api/campaigns/{$campaign->id}/pause");

        $response->assertStatus(403);

        $this->assertDatabaseHas('campaigns', [
            'id'     => $campaign->id,
            'status' => 'active',
        ]);
    });

    it('peut reprendre une campagne en pause', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser, ['status' => 'paused']);

        $response = $this->actingAs($advertiser)
            ->postJson("/api/campaigns/{$campaign->id}/resume");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Campagne reprise.');

        $this->assertDatabaseHas('campaigns', [
            'id'     => $campaign->id,
            'status' => 'active',
        ]);
    });

    it('liste les campagnes de l\'annonceur', function () {
        $advertiser = makeAdvertiser();
        Campaign::factory()->count(3)->create(['advertiser_id' => $advertiser->id]);

        $otherAdvertiser = makeAdvertiser();
        Campaign::factory()->count(2)->create(['advertiser_id' => $otherAdvertiser->id]);

        $response = $this->actingAs($advertiser)
            ->getJson('/api/campaigns');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    });

    it('affiche le détail d\'une campagne', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($advertiser)
            ->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('campaign.id', $campaign->id);
    });

    it('refuse de voir la campagne d\'un autre annonceur', function () {
        $advertiser1 = makeAdvertiser();
        $advertiser2 = makeAdvertiser();
        $campaign    = makeActiveCampaign($advertiser1);

        $response = $this->actingAs($advertiser2)
            ->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    });

    it('peut supprimer une campagne draft', function () {
        $advertiser = makeAdvertiser();
        $campaign   = Campaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status'        => 'draft',
        ]);

        $response = $this->actingAs($advertiser)
            ->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Campagne supprimée.');

        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
    });

    it('refuse de supprimer une campagne non-draft', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($advertiser)
            ->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(422);
    });

});
