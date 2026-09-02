<?php

namespace App\Notifications;

use App\Models\Journee;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PredictionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Season $season,
        public readonly Journee $journee,
        public readonly Carbon $deadline,
        public readonly int $reminderHours
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
        $label =
            $this->journee->type === 'preseason'
                ? 'tes pronostics avant-saison'
                : 'ton pronostic pour '
                    .$this->journee->name;

        return (new MailMessage)
            ->subject(
                'PronosTOP14 — Rappel : prono à compléter'
            )
            ->greeting(
                'Bonjour '
                .$notifiable->display_name
                .','
            )
            ->line(
                'Petit rappel : '
                .$label
                .' ne sont pas encore complètement renseignés.'
            )
            ->line(
                'La date limite est fixée au '
                .$this->deadline->format(
                    'd/m/Y à H:i'
                )
                .'.'
            )
            ->line(
                'Le rappel est actuellement configuré '
                .$this->reminderHours
                .' heure(s) avant la clôture.'
            )
            ->action(
                'Compléter mon prono',
                route(
                    'pronos.show',
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
