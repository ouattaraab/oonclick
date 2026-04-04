<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Campaign $campaign Campagne concernée
     * @param string   $status   Nouveau statut : 'approved' ou 'rejected'
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly string $status,
    ) {}

    /**
     * Canaux de diffusion de la notification.
     *
     * @param mixed $notifiable
     * @return array<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast', 'mail', FcmChannel::class];
    }

    /**
     * Représentation de la notification pour la persistance en base.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        $isApproved = $this->status === 'approved';

        $data = [
            'title'       => $isApproved ? 'Campagne approuvée' : 'Campagne rejetée',
            'body'        => $isApproved
                ? "Votre campagne \"{$this->campaign->title}\" a été approuvée et est prête à être activée."
                : "Votre campagne \"{$this->campaign->title}\" a été rejetée.",
            'campaign_id' => $this->campaign->id,
            'status'      => $this->status,
        ];

        if (! $isApproved && $this->campaign->rejection_reason) {
            $data['rejection_reason'] = $this->campaign->rejection_reason;
            $data['body']            .= ' Motif : ' . $this->campaign->rejection_reason;
        }

        return $data;
    }

    /**
     * Représentation broadcast de la notification (Pusher).
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        $isApproved = $this->status === 'approved';

        $payload = [
            'title'       => $isApproved ? 'Campagne approuvée' : 'Campagne rejetée',
            'body'        => $isApproved
                ? "Votre campagne \"{$this->campaign->title}\" a été approuvée et est prête à être activée."
                : "Votre campagne \"{$this->campaign->title}\" a été rejetée.",
            'campaign_id' => $this->campaign->id,
            'status'      => $this->status,
        ];

        if (! $isApproved && $this->campaign->rejection_reason) {
            $payload['rejection_reason'] = $this->campaign->rejection_reason;
        }

        return new BroadcastMessage($payload);
    }

    /**
     * Canal Pusher privé de l'annonceur.
     * Le canal est géré via toBroadcast() pour rester compatible
     * avec la signature parente sans paramètre.
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
        return 'campaign.status_changed';
    }

    /**
     * Représentation email de la notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $isApproved = $this->status === 'approved';

        $mail = (new MailMessage())
            ->subject($isApproved
                ? "Campagne approuvée : {$this->campaign->title} — oon.click"
                : "Campagne rejetée : {$this->campaign->title} — oon.click"
            )
            ->greeting("Bonjour {$notifiable->name} !");

        if ($isApproved) {
            $mail
                ->line("Bonne nouvelle ! Votre campagne **\"{$this->campaign->title}\"** a été approuvée par notre équipe de modération.")
                ->line("Budget : {$this->campaign->budget} FCFA")
                ->line("Vues maximum : {$this->campaign->max_views}")
                ->action('Activer ma campagne', url('/'))
                ->line('Vous pouvez dès maintenant procéder au paiement pour activer la diffusion de votre campagne.');
        } else {
            $mail
                ->line("Votre campagne **\"{$this->campaign->title}\"** n'a pas pu être validée par notre équipe de modération.");

            if ($this->campaign->rejection_reason) {
                $mail->line("**Motif du rejet :** {$this->campaign->rejection_reason}");
            }

            $mail
                ->line('Vous pouvez modifier votre campagne et la soumettre de nouveau après correction.')
                ->action('Modifier ma campagne', url('/'))
                ->line('Pour toute question, n\'hésitez pas à contacter notre support.');
        }

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
        $isApproved = $this->status === 'approved';

        $data = [
            'type'        => $isApproved ? 'campaign_approved' : 'campaign_rejected',
            'campaign_id' => (string) $this->campaign->id,
            'status'      => $this->status,
            'screen'      => 'campaigns',
        ];

        if (! $isApproved && $this->campaign->rejection_reason) {
            $data['rejection_reason'] = $this->campaign->rejection_reason;
        }

        return [
            'title' => $isApproved ? 'Campagne approuvée' : 'Campagne rejetée',
            'body'  => $isApproved
                ? "Votre campagne \"{$this->campaign->title}\" a été approuvée."
                : "Votre campagne \"{$this->campaign->title}\" a été rejetée.",
            'data'  => $data,
        ];
    }
}
