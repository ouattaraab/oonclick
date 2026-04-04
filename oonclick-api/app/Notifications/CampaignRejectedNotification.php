<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Campaign $campaign         Campagne rejetée
     * @param string   $rejectionReason  Motif du rejet
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly string $rejectionReason = '',
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
        $body = "Votre campagne \"{$this->campaign->title}\" n'a pas pu être validée.";

        if ($this->rejectionReason) {
            $body .= " Motif : {$this->rejectionReason}";
        }

        return [
            'title'            => 'Campagne non approuvée',
            'body'             => $body,
            'campaign_id'      => $this->campaign->id,
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
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
        $mail = (new MailMessage())
            ->subject("Campagne non approuvée : {$this->campaign->title} — oon.click")
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Votre campagne **\"{$this->campaign->title}\"** n'a malheureusement pas pu être validée par notre équipe de modération.");

        if ($this->rejectionReason) {
            $mail->line("**Motif du rejet :** {$this->rejectionReason}");
        }

        $mail
            ->line('Vous pouvez corriger votre campagne et la soumettre de nouveau.')
            ->action('Modifier ma campagne', url('/'))
            ->line('Pour toute question, n\'hésitez pas à contacter notre support.');

        return $mail;
    }

    /**
     * Payload FCM pour la notification push.
     *
     * @param mixed $notifiable
     * @return array{title: string, body: string, data: array}
     */
    public function toFcm(mixed $notifiable): array
    {
        $body = "Votre campagne \"{$this->campaign->title}\" n'a pas pu être validée.";

        if ($this->rejectionReason) {
            $body .= " Motif : {$this->rejectionReason}";
        }

        return [
            'title' => 'Campagne non approuvée',
            'body'  => $body,
            'data'  => [
                'type'             => 'campaign_rejected',
                'campaign_id'      => (string) $this->campaign->id,
                'rejection_reason' => $this->rejectionReason,
                'screen'           => 'campaigns',
            ],
        ];
    }
}
