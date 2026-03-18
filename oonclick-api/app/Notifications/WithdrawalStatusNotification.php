<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class WithdrawalStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param Withdrawal $withdrawal Retrait concerné
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
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Représentation de la notification pour la persistance en base.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        $isCompleted = $this->withdrawal->status === 'completed';

        return [
            'title'         => $isCompleted ? 'Retrait effectué' : 'Retrait échoué',
            'body'          => $isCompleted
                ? "Votre retrait de {$this->withdrawal->net_amount} FCFA a été effectué avec succès."
                : "Votre retrait de {$this->withdrawal->net_amount} FCFA a échoué. Motif : " . ($this->withdrawal->failure_reason ?? 'inconnu') . '.',
            'amount'        => $this->withdrawal->amount,
            'net_amount'    => $this->withdrawal->net_amount,
            'status'        => $this->withdrawal->status,
            'withdrawal_id' => $this->withdrawal->id,
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
        $isCompleted = $this->withdrawal->status === 'completed';

        return new BroadcastMessage([
            'title'         => $isCompleted ? 'Retrait effectué' : 'Retrait échoué',
            'body'          => $isCompleted
                ? "Votre retrait de {$this->withdrawal->net_amount} FCFA a été effectué avec succès."
                : "Votre retrait de {$this->withdrawal->net_amount} FCFA a échoué. Motif : " . ($this->withdrawal->failure_reason ?? 'inconnu') . '.',
            'amount'        => $this->withdrawal->amount,
            'net_amount'    => $this->withdrawal->net_amount,
            'status'        => $this->withdrawal->status,
            'withdrawal_id' => $this->withdrawal->id,
        ]);
    }

    /**
     * Canal Pusher privé de l'abonné.
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
        return 'withdrawal.updated';
    }

    /**
     * Représentation email de la notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $isCompleted = $this->withdrawal->status === 'completed';

        $mail = (new MailMessage())
            ->subject($isCompleted ? 'Retrait effectué — oon.click' : 'Retrait échoué — oon.click')
            ->greeting("Bonjour {$notifiable->name} !");

        if ($isCompleted) {
            $mail
                ->line("Votre retrait de **{$this->withdrawal->net_amount} FCFA** a été traité avec succès.")
                ->line("Montant demandé : {$this->withdrawal->amount} FCFA")
                ->line("Frais déduits : {$this->withdrawal->fee} FCFA")
                ->line("Montant net reçu : {$this->withdrawal->net_amount} FCFA")
                ->action('Voir mon wallet', url('/'))
                ->line('Merci de votre confiance.');
        } else {
            $mail
                ->line("Votre retrait de **{$this->withdrawal->net_amount} FCFA** a malheureusement échoué.")
                ->line('Motif : ' . ($this->withdrawal->failure_reason ?? 'Erreur inconnue'))
                ->line('Les fonds ont été recrédités sur votre wallet.')
                ->line('Si le problème persiste, veuillez contacter notre support.');
        }

        return $mail;
    }
}
