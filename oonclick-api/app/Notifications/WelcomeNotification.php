<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une nouvelle instance de la notification.
     *
     * @param User $user Utilisateur nouvellement inscrit
     */
    public function __construct(
        public readonly User $user,
    ) {}

    /**
     * Canaux de diffusion de la notification.
     *
     * @param mixed $notifiable
     * @return array<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
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
            'title' => 'Bienvenue sur oon.click !',
            'body'  => 'Votre compte est activé. Complétez votre profil pour recevoir vos premières publicités et gagner des FCFA.',
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
            ->subject('Bienvenue sur oon.click !')
            ->greeting("Bienvenue {$notifiable->name} !")
            ->line('Votre compte oon.click est maintenant activé. Vous pouvez dès à présent commencer à gagner des FCFA en regardant des publicités.')
            ->line('Pour bien démarrer, suivez ces étapes :')
            ->line('**1. Complétez votre profil** — Renseignez vos informations personnelles pour recevoir des publicités ciblées.')
            ->line('**2. Activez vos notifications** — Ne ratez aucune opportunité de gain en activant les notifications push.')
            ->line('**3. Regardez votre première pub** — Chaque visionnage complet vous rapporte des FCFA directement sur votre wallet.')
            ->action('Compléter mon profil', url('/'))
            ->line('Merci de rejoindre la communauté oon.click. Bonne chance !');
    }
}
