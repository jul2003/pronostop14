<?php

namespace App\Notifications;

use App\Models\Journee;
use App\Models\Season;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ResultsAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Season $season,
        public readonly Journee $journee,
        public readonly Collection $ranking
    ) {
    }

    public function via(
        object $notifiable
    ): array {
        return [
            'mail',
        ];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {
        $mail = (new MailMessage)
            ->subject(
                'PronosTOP14 — Résultats disponibles : '
                .$this->journee->name
            )
            ->greeting(
                'Bonjour '
                .$notifiable->display_name
                .','
            )
            ->line(
                'Tous les résultats de '
                .$this->journee->name
                .' sont maintenant enregistrés.'
            )
            ->line(
                'Classement de la journée :'
            );

        foreach ($this->ranking as $score) {
            $points = (int) $score->total_points;

            $mail->line(
                (int) $score->rank
                .'. '
                .$score->user->display_name
                .' — '
                .$points
                .' '
                .($points === 1 ? 'point' : 'points')
            );
        }

        return $mail
            ->action(
                'Voir les résultats',
                route(
                    'results.journee',
                    [
                        $this->season,
                        $this->journee,
                    ]
                )
            )
            ->salutation(
                'À bientôt sur PronosTOP14'
            );
    }

    public function toArray(
        object $notifiable
    ): array {
        return [];
    }
}
