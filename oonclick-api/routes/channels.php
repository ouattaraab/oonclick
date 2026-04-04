<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Ici sont définis les canaux Pusher autorisés pour le broadcast.
| Chaque closure retourne true/false pour autoriser ou refuser l'accès.
|
*/

// Canal subscriber — accessible uniquement par le subscriber lui-même
Broadcast::channel('subscriber.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal advertiser — accessible uniquement par l'annonceur lui-même
Broadcast::channel('advertiser.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->role === 'advertiser';
});

// Canal admin — accessible uniquement par les administrateurs
Broadcast::channel('admin', function ($user) {
    return $user->role === 'admin';
});

// Canal campagne — progression en temps réel (canal public, lecture seule)
// Accessible à l'annonceur propriétaire de la campagne.
Broadcast::channel('campaign.{campaignId}', function ($user, $campaignId) {
    return \App\Models\Campaign::where('id', $campaignId)
        ->where('advertiser_id', $user->id)
        ->exists();
});
