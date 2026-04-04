<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Withdrawal $withdrawal Retrait ayant échoué
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
            'title'          => 'Retrait échoué',
            'body'           => "Votre retrait de {$this->withdrawal->net_amount} FCFA a échoué. "
                              . 'Motif : ' . ($this->withdrawal->failure_reason ?? 'inconnu') . '. '
                              . 'Les fonds ont été recrédités sur votre wallet.',
            'amount'         => $this->withdrawal->amount,
            'net_amount'     => $this->withdrawal->net_amount,
            'status'         => 'failed',
            'failure_reason' => $this->withdrawal->failure_reason,
            'withdrawal_id'  => $this->withdrawal->id,
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
            ->subject('Retrait échoué — oon.click')
            ->greeting("Bonjour {$notifiable->name} !")
            ->line("Votre retrait de **{$this->withdrawal->net_amount} FCFA** a malheureusement échoué.")
            ->line('Motif : ' . ($this->withdrawal->failure_reason ?? 'Erreur inconnue'))
            ->line('Les fonds ont été automatiquement recrédités sur votre wallet.')
            ->line('Si le problème persiste, veuillez vérifier vos informations de paiement ou contacter notre support.')
            ->action('Voir mon wallet', url('/'));
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
            'title' => 'Retrait échoué',
            'body'  => "Votre retrait de {$this->withdrawal->net_amount} FCFA a échoué. Les fonds ont été recrédités.",
            'data'  => [
                'type'           => 'withdrawal_failed',
                'withdrawal_id'  => (string) $this->withdrawal->id,
                'failure_reason' => $this->withdrawal->failure_reason ?? '',
                'screen'         => 'wallet',
            ],
        ];
    }
}
