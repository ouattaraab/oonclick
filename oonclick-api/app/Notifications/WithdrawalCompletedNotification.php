<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Withdrawal $withdrawal Retrait effectué avec succès
     */
    public function __construct(
        public readonly Withdrawal $withdrawal,
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
            'title'         => 'Retrait effectué',
            'body'          => "Votre retrait de {$this->withdrawal->net_amount} FCFA a été effectué avec succès.",
            'amount'        => $this->withdrawal->amount,
            'net_amount'    => $this->withdrawal->net_amount,
            'status'        => 'completed',
            'withdrawal_id' => $this->withdrawal->id,
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
            ->subject('Retrait effectué — oon.click')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Votre retrait de **{$this->withdrawal->net_amount} FCFA** a été traité avec succès.")
            ->line("Montant demandé : {$this->withdrawal->amount} FCFA")
            ->line("Frais déduits : {$this->withdrawal->fee} FCFA")
            ->line("Montant net reçu : {$this->withdrawal->net_amount} FCFA")
            ->line("Opérateur : " . strtoupper($this->withdrawal->mobile_operator))
            ->action('Voir mon wallet', url('/'))
            ->line('Merci de votre confiance.');
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
            'title' => 'Retrait effectué',
            'body'  => "Votre retrait de {$this->withdrawal->net_amount} FCFA a été effectué avec succès.",
            'data'  => [
                'type'          => 'withdrawal_completed',
                'withdrawal_id' => (string) $this->withdrawal->id,
                'net_amount'    => (string) $this->withdrawal->net_amount,
                'screen'        => 'wallet',
            ],
        ];
    }
}
