<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Journee;
use App\Models\PredictionReminder;
use App\Models\Season;
use App\Models\SeasonPreseasonPrediction;
use App\Models\User;
use App\Notifications\PredictionReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PredictionReminderService
{
    public function __construct(
        private readonly PreseasonDeadlineService $preseasonDeadlineService
    ) {
    }

    public function sendDueReminders(): int
    {
        $reminderHours =
            $this->reminderHours();

        /*
         * Ici on utilise volontairement l'heure réelle.
         *
         * Une date simulée dans l'administration ne doit pas
         * déclencher de vrais emails programmés sur le VPS.
         */
        $now = now();

        $sentCount = 0;

        $seasons = Season::query()
            ->where(
                'is_active',
                true
            )
            ->get();

        foreach ($seasons as $season) {
            $players =
                $this->playersToNotify(
                    $season
                );

            if ($players->isEmpty()) {
                continue;
            }

            $sentCount +=
                $this->sendPreseasonReminders(
                    $season,
                    $players,
                    $now,
                    $reminderHours
                );

            $sentCount +=
                $this->sendJourneeReminders(
                    $season,
                    $players,
                    $now,
                    $reminderHours
                );
        }

        return $sentCount;
    }

    private function sendPreseasonReminders(
        Season $season,
        Collection $players,
        Carbon $now,
        int $reminderHours
    ): int {
        $journee = $season
            ->journees()
            ->where(
                'type',
                'preseason'
            )
            ->first();

        if (! $journee) {
            return 0;
        }

        $questionIds = $season
            ->preseasonQuestions()
            ->where(
                'is_active',
                true
            )
            ->get()
            ->reject(
                fn ($question) =>
                    $question
                        ->hasOfficialResult()
            )
            ->pluck('id')
            ->values();

        /*
         * Plus aucune question encore modifiable :
         * aucun rappel nécessaire.
         */
        if ($questionIds->isEmpty()) {
            return 0;
        }

        $sentCount = 0;

        foreach ($players as $player) {
            if (
                $this->alreadyReminded(
                    $player,
                    $journee
                )
            ) {
                continue;
            }

            /*
             * C'est ici que la deadline personnelle d'un
             * joueur arrivé en cours de saison est utilisée.
             */
            $deadline =
                $this
                    ->preseasonDeadlineService
                    ->deadlineForUser(
                        $season,
                        $player
                    );

            if (! $deadline) {
                continue;
            }

            if (
                ! $this->isReminderDue(
                    $deadline,
                    $now,
                    $reminderHours
                )
            ) {
                continue;
            }

            $answeredCount =
                SeasonPreseasonPrediction::query()
                    ->where(
                        'season_id',
                        $season->id
                    )
                    ->where(
                        'user_id',
                        $player->id
                    )
                    ->whereIn(
                        'question_id',
                        $questionIds
                    )
                    ->count();

            if (
                $answeredCount
                >= $questionIds->count()
            ) {
                continue;
            }

            if (
                $this->sendReminder(
                    $player,
                    $season,
                    $journee,
                    $deadline,
                    $reminderHours
                )
            ) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function sendJourneeReminders(
        Season $season,
        Collection $players,
        Carbon $now,
        int $reminderHours
    ): int {
        $journees = $season
            ->journees()
            ->where(
                'type',
                '!=',
                'preseason'
            )
            ->where(
                'predictions_enabled',
                true
            )
            ->with([
                'matches.predictionDeadlineException',
                'matches.pronos',
            ])
            ->get();

        $sentCount = 0;

        foreach ($journees as $journee) {
            $expectedMatches =
                $journee
                    ->expectedMatchesCount();

            if (
                $expectedMatches === null
                || $expectedMatches < 1
                || $journee->matches->count()
                    !== $expectedMatches
            ) {
                continue;
            }

            foreach ($players as $player) {
                if (
                    $this->alreadyReminded(
                        $player,
                        $journee
                    )
                ) {
                    continue;
                }

                $missingMatches =
                    $journee
                        ->matches
                        ->filter(
                            function ($match) use (
                                $player
                            ) {
                                return ! $match
                                    ->pronos
                                    ->contains(
                                        fn ($prono) =>
                                            (int) $prono->user_id
                                            === (int) $player->id
                                    );
                            }
                        );

                /*
                 * Le joueur a rempli tous les matchs.
                 */
                if ($missingMatches->isEmpty()) {
                    continue;
                }

                /*
                 * On cherche la prochaine deadline encore ouverte
                 * parmi les matchs que le joueur n'a pas remplis.
                 *
                 * Cela tient aussi compte des éventuelles exceptions
                 * de date de clôture par match.
                 */
                $openDeadlines =
                    $missingMatches
                        ->map(
                            function ($match) use (
                                $journee
                            ) {
                                return $match
                                    ->predictionDeadlineException
                                    ?->prediction_deadline
                                    ?? $journee
                                        ->first_match_at;
                            }
                        )
                        ->filter(
                            fn ($deadline) =>
                                $deadline
                                && $deadline->gt(
                                    now()
                                )
                        )
                        ->sort()
                        ->values();

                $deadline =
                    $openDeadlines->first();

                if (! $deadline) {
                    continue;
                }

                if (
                    ! $this->isReminderDue(
                        $deadline,
                        $now,
                        $reminderHours
                    )
                ) {
                    continue;
                }

                if (
                    $this->sendReminder(
                        $player,
                        $season,
                        $journee,
                        $deadline,
                        $reminderHours
                    )
                ) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    private function playersToNotify(
        Season $season
    ): Collection {
        return $season
            ->players()
            ->where(
                'users.is_active',
                true
            )
            ->where(
                'users.notify_prediction_reminder',
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
    }

    private function reminderHours(): int
    {
        $setting =
            AppSetting::query()
                ->where(
                    'key',
                    'prediction_reminder_hours'
                )
                ->first();

        return max(
            1,
            (int) (
                $setting?->typedValue()
                ?? 24
            )
        );
    }

    private function isReminderDue(
        Carbon $deadline,
        Carbon $now,
        int $reminderHours
    ): bool {
        if (
            $now->greaterThanOrEqualTo(
                $deadline
            )
        ) {
            return false;
        }

        $reminderStartsAt =
            $deadline
                ->copy()
                ->subHours(
                    $reminderHours
                );

        return $now
            ->greaterThanOrEqualTo(
                $reminderStartsAt
            );
    }

    private function alreadyReminded(
        User $player,
        Journee $journee
    ): bool {
        return PredictionReminder::query()
            ->where(
                'user_id',
                $player->id
            )
            ->where(
                'journee_id',
                $journee->id
            )
            ->exists();
    }

    private function sendReminder(
        User $player,
        Season $season,
        Journee $journee,
        Carbon $deadline,
        int $reminderHours
    ): bool {
        try {
            Notification::send(
                $player,
                new PredictionReminderNotification(
                    $season,
                    $journee,
                    $deadline,
                    $reminderHours
                )
            );
        } catch (Throwable $exception) {
            Log::error(
                'Échec du rappel de pronostic.',
                [
                    'user_id' =>
                        $player->id,

                    'season_id' =>
                        $season->id,

                    'journee_id' =>
                        $journee->id,

                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return false;
        }

        PredictionReminder::create([
            'user_id' =>
                $player->id,

            'season_id' =>
                $season->id,

            'journee_id' =>
                $journee->id,

            'deadline_at' =>
                $deadline,

            'sent_at' =>
                now(),
        ]);

        return true;
    }
}
