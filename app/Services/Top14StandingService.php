<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\Season;
use Illuminate\Support\Collection;

class Top14StandingService
{
    public const MAX_POINTS_PER_REMAINING_MATCH = 5;

    /**
     * Calcule le classement TOP 14 jusqu'à une journée cible.
     *
     * Cette méthode sera utilisée par les automatismes :
     *
     * - champion d'automne ;
     * - position TOP 14 à Jx ;
     * - 13e ;
     * - 14e ;
     * - barrages ;
     * - demi-finales ;
     * - access match.
     */
    public function standingsThroughJournee(
        Season $season,
        int $targetJourneeNumber
    ): Collection {
        if (
            $targetJourneeNumber < 1
            || $targetJourneeNumber > 26
        ) {
            return collect();
        }

        $journees = $season
            ->journees()
            ->where(
                'type',
                'regular'
            )
            ->whereBetween(
                'number',
                [
                    1,
                    $targetJourneeNumber,
                ]
            )
            ->with([
                'matches.homeClub',
                'matches.awayClub',
            ])
            ->reorder()
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        return $this->standingsForJournees(
            season: $season,
            journees: $journees,
            penaltyReferenceJourneeNumber:
                $targetJourneeNumber,
            remainingTargetJourneeNumber:
                $targetJourneeNumber,
        );
    }

    /**
     * Calcule un classement à partir d'une collection
     * précise de journées.
     *
     * Cela permet notamment à la page publique de conserver
     * sa logique actuelle consistant à ne prendre en compte
     * que certaines journées selon la date.
     */
    public function standingsForJournees(
        Season $season,
        Collection $journees,
        ?int $penaltyReferenceJourneeNumber = null,
        ?int $remainingTargetJourneeNumber = null
    ): Collection {
        $clubs = $season
            ->clubs()
            ->wherePivot(
                'competition',
                'top14'
            )
            ->orderBy('name')
            ->get();

        $rowsByClubId = [];

        foreach ($clubs as $club) {
            $rowsByClubId[$club->id] = [
                'club' => $club,

                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,

                'home_played' => 0,
                'away_played' => 0,

                'offensive_bonus' => 0,
                'defensive_bonus' => 0,
                'bonus_total' => 0,

                /*
                 * Points obtenus uniquement sur le terrain.
                 */
                'sporting_points' => 0,

                /*
                 * Total des sanctions applicables.
                 *
                 * Cette valeur est toujours positive.
                 * Exemple : 4 signifie -4 au classement.
                 */
                'penalty_points' => 0,

                /*
                 * Points officiels après sanction.
                 */
                'points' => 0,

                'remaining_matches' => 0,
                'max_points' => 0,

                'rank' => null,
            ];
        }

        foreach ($journees as $journee) {
            foreach ($journee->matches as $match) {
                if (blank($match->actual_result)) {
                    continue;
                }

                if (
                    ! isset(
                        $rowsByClubId[
                            $match->home_club_id
                        ],
                        $rowsByClubId[
                            $match->away_club_id
                        ]
                    )
                ) {
                    continue;
                }

                $this->applyMatchToStandings(
                    $rowsByClubId,
                    $match
                );
            }
        }

        if (
            $penaltyReferenceJourneeNumber
            === null
        ) {
            $penaltyReferenceJourneeNumber =
                (int) (
                    $journees
                        ->filter(
                            fn ($journee) =>
                                $journee->type
                                === 'regular'
                        )
                        ->max('number')
                    ?? 0
                );
        }

        $penaltiesByClubId =
            $this->penaltiesByClubId(
                $season,
                $penaltyReferenceJourneeNumber
            );

        foreach (
            $rowsByClubId
            as $clubId => &$row
        ) {
            $row['penalty_points'] =
                (int) (
                    $penaltiesByClubId[
                        $clubId
                    ]
                    ?? 0
                );

            $row['points'] =
                (int) $row[
                    'sporting_points'
                ]
                - (int) $row[
                    'penalty_points'
                ];

            if (
                $remainingTargetJourneeNumber
                !== null
            ) {
                $row['remaining_matches'] =
                    max(
                        0,
                        $remainingTargetJourneeNumber
                        - (int) $row['played']
                    );

                /*
                 * Le maximum possible part bien du total
                 * officiel après pénalité.
                 *
                 * Exemple :
                 *
                 * 30 points sportifs
                 * -4 de pénalité
                 * 3 matchs restants
                 *
                 * maximum = 26 + 3 × 5 = 41.
                 */
                $row['max_points'] =
                    (int) $row['points']
                    + (
                        $row['remaining_matches']
                        * self::MAX_POINTS_PER_REMAINING_MATCH
                    );
            } else {
                $row['remaining_matches'] = 0;

                $row['max_points'] =
                    (int) $row['points'];
            }
        }

        unset($row);

        $standings = collect(
            $rowsByClubId
        )
            ->sort(
                fn (
                    array $a,
                    array $b
                ) =>
                    $this->compareStandingRows(
                        $a,
                        $b
                    )
            )
            ->values();

        return $this->applyRanks(
            $standings
        );
    }

    /**
     * Retourne le total des pénalités applicables
     * pour chaque club à une journée donnée.
     */
    public function penaltiesByClubId(
        Season $season,
        int $journeeNumber
    ): Collection {
        if ($journeeNumber < 1) {
            return collect();
        }

        return $season
            ->clubPenalties()
            ->where(
                'effective_from_journee',
                '<=',
                $journeeNumber
            )
            ->selectRaw(
                'club_id, SUM(points_deduction) as total_points_deduction'
            )
            ->groupBy('club_id')
            ->pluck(
                'total_points_deduction',
                'club_id'
            )
            ->map(
                fn ($points) =>
                    (int) $points
            );
    }

    private function applyMatchToStandings(
        array &$rowsByClubId,
        MatchGame $match
    ): void {
        $homeClubId =
            (int) $match->home_club_id;

        $awayClubId =
            (int) $match->away_club_id;

        $rowsByClubId[
            $homeClubId
        ]['played']++;

        $rowsByClubId[
            $homeClubId
        ]['home_played']++;

        $rowsByClubId[
            $awayClubId
        ]['played']++;

        $rowsByClubId[
            $awayClubId
        ]['away_played']++;

        $result = strtolower(
            trim(
                (string) $match->actual_result
            )
        );

        if ($result === 'v') {
            $rowsByClubId[
                $homeClubId
            ]['won']++;

            $rowsByClubId[
                $homeClubId
            ]['sporting_points'] += 4;

            $rowsByClubId[
                $awayClubId
            ]['lost']++;
        } elseif ($result === 'd') {
            $rowsByClubId[
                $awayClubId
            ]['won']++;

            $rowsByClubId[
                $awayClubId
            ]['sporting_points'] += 4;

            $rowsByClubId[
                $homeClubId
            ]['lost']++;
        } elseif ($result === 'n') {
            $rowsByClubId[
                $homeClubId
            ]['drawn']++;

            $rowsByClubId[
                $homeClubId
            ]['sporting_points'] += 2;

            $rowsByClubId[
                $awayClubId
            ]['drawn']++;

            $rowsByClubId[
                $awayClubId
            ]['sporting_points'] += 2;
        }

        $this->applyBonusToStanding(
            $rowsByClubId[
                $homeClubId
            ],
            $match->actual_home_bonus
        );

        $this->applyBonusToStanding(
            $rowsByClubId[
                $awayClubId
            ],
            $match->actual_away_bonus
        );
    }

    private function applyBonusToStanding(
        array &$row,
        ?string $bonus
    ): void {
        $bonus = $this->normalizeBonus(
            $bonus
        );

        if ($bonus === null) {
            return;
        }

        if ($bonus === 'o') {
            $row[
                'offensive_bonus'
            ]++;

            $row[
                'bonus_total'
            ]++;

            $row[
                'sporting_points'
            ]++;

            return;
        }

        if ($bonus === 'd') {
            $row[
                'defensive_bonus'
            ]++;

            $row[
                'bonus_total'
            ]++;

            $row[
                'sporting_points'
            ]++;
        }
    }

    private function normalizeBonus(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = strtolower(
            trim($value)
        );

        return in_array(
            $value,
            [
                'o',
                'd',
            ],
            true
        )
            ? $value
            : null;
    }

    private function compareStandingRows(
        array $a,
        array $b
    ): int {
        /*
         * Le premier critère est désormais le total
         * officiel après éventuelles pénalités.
         */
        $comparison =
            $b['points']
            <=> $a['points'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison =
            $b['won']
            <=> $a['won'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison =
            $b['drawn']
            <=> $a['drawn'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison =
            $b['bonus_total']
            <=> $a['bonus_total'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison =
            $a['lost']
            <=> $b['lost'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = strcasecmp(
            $a['club']->name,
            $b['club']->name
        );

        if ($comparison !== 0) {
            return $comparison;
        }

        return (int) $a['club']->id
            <=> (int) $b['club']->id;
    }

    private function applyRanks(
        Collection $standings
    ): Collection {
        $rank = 0;
        $position = 0;
        $previousKey = null;

        return $standings->map(
            function (
                array $row
            ) use (
                &$rank,
                &$position,
                &$previousKey
            ) {
                $position++;

                $currentKey = [
                    $row['points'],
                    $row['won'],
                    $row['drawn'],
                    $row['bonus_total'],
                    -$row['lost'],
                ];

                if (
                    $previousKey
                    !== $currentKey
                ) {
                    $rank = $position;
                }

                $row['rank'] = $rank;

                $previousKey =
                    $currentKey;

                return $row;
            }
        );
    }
}
