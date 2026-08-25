<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\AppDateService;
use App\Services\Top14StandingService;
use Carbon\Carbon;

class TeamStandingController extends Controller
{
    public function index()
    {
        $season = Season::where(
            'is_active',
            true
        )->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with(
                    'error',
                    'Aucune saison active pour le moment.'
                );
        }

        return redirect()->route(
            'team-standings.season',
            $season
        );
    }

    public function season(
        Season $season,
        AppDateService $dateService,
        Top14StandingService $standingService
    ) {
        $currentDate = $dateService->now();

        $seasons = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $clubs = $season
            ->clubs()
            ->wherePivot(
                'competition',
                'top14'
            )
            ->orderBy('name')
            ->get();

        $journees = $season
            ->journees()
            ->where(
                'type',
                'regular'
            )
            ->with([
                'matches.homeClub',
                'matches.awayClub',
            ])
            ->reorder()
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        /*
         * Journées utilisées pour calculer les résultats sportifs.
         *
         * On conserve le comportement existant :
         * une journée n'entre dans le classement que lorsqu'elle
         * a commencé et possède au moins un résultat.
         */
        $includedJournees = $journees
            ->filter(
                function ($journee) use ($currentDate) {
                    $journeeDate =
                        $this->journeeDate(
                            $journee
                        );

                    if (
                        ! $journeeDate
                        || $journeeDate->gt(
                            $currentDate
                        )
                    ) {
                        return false;
                    }

                    return $journee
                        ->matches
                        ->contains(
                            fn ($match) =>
                                filled(
                                    $match->actual_result
                                )
                        );
                }
            )
            ->values();

        /*
         * Journée de référence pour les pénalités.
         *
         * Elle est volontairement indépendante des résultats :
         * une pénalité qui entre en vigueur à J10 doit être appliquée
         * dès que J10 commence, même si aucun résultat de J10
         * n'a encore été enregistré.
         *
         * J1 signifie "dès le début de la saison". C'est pourquoi
         * la référence minimale est toujours 1.
         */
        $startedJourneeNumber = $journees
            ->filter(
                function ($journee) use ($currentDate) {
                    $journeeDate =
                        $this->journeeDate(
                            $journee
                        );

                    return $journeeDate
                        && $journeeDate->lte(
                            $currentDate
                        );
                }
            )
            ->max('number');

        $penaltyReferenceJournee = max(
            1,
            (int) (
                $startedJourneeNumber
                ?? 1
            )
        );

        $standings = $standingService
            ->standingsForJournees(
                season: $season,
                journees: $includedJournees,
                penaltyReferenceJourneeNumber:
                    $penaltyReferenceJournee,
                remainingTargetJourneeNumber:
                    null,
            );

        $playedMatchesCount =
            $includedJournees
                ->sum(
                    function ($journee) {
                        return $journee
                            ->matches
                            ->filter(
                                fn ($match) =>
                                    filled(
                                        $match->actual_result
                                    )
                            )
                            ->count();
                    }
                );

        return view(
            'team-standings.index',
            [
                'seasons' =>
                    $seasons,

                'selectedSeason' =>
                    $season,

                'currentDate' =>
                    $currentDate,

                'clubs' =>
                    $clubs,

                'includedJournees' =>
                    $includedJournees,

                'standings' =>
                    $standings,

                'playedMatchesCount' =>
                    $playedMatchesCount,

                'penaltyReferenceJournee' =>
                    $penaltyReferenceJournee,
            ]
        );
    }

    private function journeeDate(
        $journee
    ): ?Carbon {
        if (! $journee->first_match_at) {
            return null;
        }

        return $journee->first_match_at
            instanceof Carbon
                ? $journee
                    ->first_match_at
                    ->copy()
                : Carbon::parse(
                    $journee
                        ->first_match_at
                );
    }
}
