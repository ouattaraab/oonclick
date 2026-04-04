<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Campaign $campaign Campagne approuvée
     */
    public function __construct(
        public readonly Campaign $campaign,
    ) {}

    /**
     * Canaux de diffusion de la notification.
     *
     * @param mixed $notifiable
     * @return array<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail', FcmChannel::class];
    }

    /**
     * Représentation de la notification pour la persistance en base.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'title'       => 'Campagne approuvée',
            'body'        => "Votre campagne \"{$this->campaign->title}\" a été approuvée et est maintenant en diffusion.",
            'campaign_id' => $this->campaign->id,
            'status'      => 'approved',
        ];
    }

    /**
     * Représentation email de la notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Campagne approuvée : {$this->campaign->title} — oon.click")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Bonne nouvelle ! Votre campagne **\"{$this->campaign->title}\"** a été approuvée par notre équipe de modération.")
            ->line("Elle est maintenant en diffusion auprès de notre réseau d'abonnés.")
            ->line("Budget : {$this->campaign->budget} FCFA")
            ->line("Vues maximum : {$this->campaign->max_views}")
            ->action('Voir ma campagne', url('/'))
            ->line('Merci de faire confiance à oon.click pour votre communication.');
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
            'title' => 'Campagne approuvée',
            'body'  => "Votre campagne \"{$this->campaign->title}\" a été approuvée et est en diffusion.",
            'data'  => [
                'type'        => 'campaign_approved',
                'campaign_id' => (string) $this->campaign->id,
                'screen'      => 'campaigns',
            ],
        ];
    }
}
