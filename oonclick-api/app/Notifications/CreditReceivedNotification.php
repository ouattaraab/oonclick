<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CreditReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param int $amount      Montant crédité en FCFA
     * @param int $campaignId  Identifiant de la campagne visionnée
     * @param int $newBalance  Nouveau solde du wallet après crédit
     */
    public function __construct(
        public readonly int $amount,
        public readonly int $campaignId,
        public readonly int $newBalance,
    ) {}

    /**
     * Canaux de diffusion de la notification.
     *
     * @param mixed $notifiable
     * @return array<string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Only send push notification if user has granted C5 consent
        if (\App\Models\UserConsent::hasConsent($notifiable->id, 'C5')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Représentation de la notification pour la persistance en base.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title'       => 'Crédit reçu',
            'body'        => "Vous avez gagné {$this->amount} FCFA !",
            'amount'      => $this->amount,
            'campaign_id' => $this->campaignId,
            'new_balance' => $this->newBalance,
        ];
    }

    /**
     * Représentation broadcast de la notification (Pusher).
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title'       => 'Crédit reçu',
            'body'        => "Vous avez gagné {$this->amount} FCFA !",
            'amount'      => $this->amount,
            'campaign_id' => $this->campaignId,
            'new_balance' => $this->newBalance,
        ]);
    }

    /**
     * Canal Pusher privé de l'abonné.
     * La méthode broadcastOn() de la classe parente n'accepte pas de paramètre,
     * donc on utilise toBroadcast() pour personnaliser le canal.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Nom de l'événement broadcast.
     *
     * @return string
     */
    public function broadcastType(): string
    {
        return 'credit.received';
    }

    /**
     * Payload FCM pour la notification push.
     *
     * @param mixed $notifiable
     * @return array{title: string, body: string, data: array}
     */
    public function toFcm(mixed $notifiable): array
    {
        return [
            'title' => 'Crédit reçu',
            'body'  => "Vous avez gagné {$this->amount} FCFA !",
            'data'  => [
                'type'        => 'credit_received',
                'amount'      => (string) $this->amount,
                'campaign_id' => (string) $this->campaignId,
                'new_balance' => (string) $this->newBalance,
                'screen'      => 'wallet',
            ],
        ];
    }
}
