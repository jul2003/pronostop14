<?php

namespace App\Services;

use App\Models\Journee;
use App\Notifications\PredictionAvailableNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PredictionAnnouncementService
{
    private const REQUIRED_MATCHES_COUNT = 7;

    public function sendIfReady(
        Journee $journee
    ): bool {
        /*
         * On recharge l'état réel depuis la base.
         *
         * C'est notamment important lorsque le service
         * est appelé juste après la création du 7e match.
         */
        $journee = Journee::query()
            ->with('season')
            ->find(
                $journee->getKey()
            );

        if (! $journee) {
            return false;
        }

        /*
         * Une seule notification par journée.
         */
        if (
            $journee
                ->prediction_announcement_sent_at
        ) {
            return false;
        }

        /*
         * La notification demandée concerne les
         * journées TOP 14 classiques à 7 matchs.
         *
         * Les phases finales ne sont donc pas
         * concernées par ce mécanisme.
         */
        if ($journee->type !== 'regular') {
            return false;
        }

        /*
         * Condition 1 :
         * la saisie des pronostics est activée.
         */
        if (
            ! $journee
                ->predictions_enabled
        ) {
            return false;
        }

        /*
         * Condition 2 :
         * une date de premier match existe.
         */
        if (
            ! $journee
                ->first_match_at
        ) {
            return false;
        }

        /*
         * Petite sécurité supplémentaire :
         * il faut réellement pouvoir encore saisir
         * le prono.
         *
         * Cela évite d'envoyer un "nouveau prono"
         * avec une date déjà dépassée.
         */
        if (
            ! $journee
                ->isPredictionOpen()
        ) {
            return false;
        }

        /*
         * Condition 3 :
         * les 7 matchs doivent être présents.
         */
        $matchesCount =
            $journee
                ->matches()
                ->count();

        if (
            $matchesCount
            !== self::REQUIRED_MATCHES_COUNT
        ) {
            return false;
        }

        /*
         * Tous les participants actifs de la saison
         * ayant une adresse email sont avertis.
         *
         * Cela inclut également un admin qui participe
         * lui-même à la saison.
         */
        $players =
            $journee
                ->season
                ->players()
                ->where(
                    'users.is_active',
                    true
                )
                ->whereNotNull(
                    'users.email'
                )
                ->where(
                    'users.email',
                    '<>',
                    ''
                )
                ->get();

        if ($players->isEmpty()) {
            Log::warning(
                'Notification de nouveau prono non envoyée : aucun destinataire.',
                [
                    'season_id' =>
                        $journee->season_id,

                    'journee_id' =>
                        $journee->id,
                ]
            );

            return false;
        }

        try {
            Notification::send(
                $players,
                new PredictionAvailableNotification(
                    $journee->season,
                    $journee
                )
            );
        } catch (Throwable $exception) {
            /*
             * Une panne SMTP ne doit surtout pas
             * empêcher l'admin d'enregistrer sa
             * journée ou ses matchs.
             *
             * Comme la date d'envoi reste NULL,
             * une prochaine sauvegarde réessaiera.
             */
            Log::error(
                'Échec de la notification de nouveau prono.',
                [
                    'season_id' =>
                        $journee->season_id,

                    'journee_id' =>
                        $journee->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return false;
        }

        /*
         * saveQuietly évite de rappeler l'observer
         * lorsque l'on mémorise l'envoi.
         */
        $journee->forceFill([
            'prediction_announcement_sent_at' =>
                now(),
        ])->saveQuietly();

        Log::info(
            'Notification de nouveau prono envoyée.',
            [
                'season_id' =>
                    $journee->season_id,

                'journee_id' =>
                    $journee->id,

                'recipients_count' =>
                    $players->count(),
            ]
        );

        return true;
    }
}
