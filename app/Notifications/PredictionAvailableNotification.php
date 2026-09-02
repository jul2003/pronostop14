<?php

namespace App\Notifications;

use App\Models\Journee;
use App\Models\Season;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PredictionAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Season $season,
        public readonly Journee $journee
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
        $deadline =
            $this->journee
                ->first_match_at
                ->format(
                    'd/m/Y à H:i'
                );

        return (new MailMessage)
            ->subject(
                'PronosTOP14 — Nouveau prono à saisir : '
                .$this->journee->name
            )
            ->greeting(
                'Bonjour '
                .$notifiable->display_name
                .','
            )
            ->line(
                'Un nouveau pronostic est disponible pour '
                .$this->journee->name
                .' de la saison '
                .$this->season->name
                .'.'
            )
            ->line(
                'Les 7 matchs sont maintenant disponibles et la saisie des pronostics est ouverte.'
            )
            ->line(
                'Tu peux saisir ton pronostic jusqu’au '
                .$deadline
                .'.'
            )
            ->action(
                'Saisir mon prono',
                route(
                    'pronos.show',
                    [
                        'season' =>
                            $this->season,

                        'journee' =>
                            $this->journee,
                    ]
                )
            )
            ->line(
                'Bonne chance !'
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
