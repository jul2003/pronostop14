<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\Season;
use Illuminate\Support\Collection;

class KnockoutMatchSetupService
{
    private const TARGET_REGULAR_JOURNEE = 26;

    private const MAX_POINTS_PER_REMAINING_MATCH = 5;

    public function eligibleClubsForJournee(Season $season, Journee $journee): Collection
    {
        return match ($journee->type) {
            'regular' => $this->top14Clubs($season),
            'prod2_final' => $this->prod2Clubs($season),
            'access_match' => $this->accessMatchEligibleClubs($season),
            'top14_playoff' => $this->top14PlayoffEligibleClubs($season),
            'top14_semifinal' => $this->top14SemifinalEligibleClubs($season),
            'top14_final' => $this->top14FinalEligibleClubs($season),
            default => $this->top14Clubs($season),
        };
    }

    public function automaticSetupForJournee(Season $season, Journee $journee): array
    {
        return match ($journee->type) {
            'top14_playoff' => $this->top14PlayoffAutomaticSetup($season),
            'access_match' => $this->accessMatchAutomaticSetup($season),
            'top14_semifinal' => $this->top14SemifinalAutomaticSetup($season),
            'top14_final' => $this->top14FinalAutomaticSetup($season),
            default => $this->emptyAutomaticSetup(),
        };
    }

    private function top14PlayoffAutomaticSetup(Season $season): array
    {
        $position3 = $this->certifiedTop14ClubAtPosition($season, 3);
        $position4 = $this->certifiedTop14ClubAtPosition($season, 4);
        $position5 = $this->certifiedTop14ClubAtPosition($season, 5);
        $position6 = $this->certifiedTop14ClubAtPosition($season, 6);

        $pairs = [
            [
                'position' => 1,
                'label' => 'Barrage 1',
                'home' => $position4,
                'away' => $position5,
                'home_label' => 'Équipe 1',
                'away_label' => 'Équipe 2',
                'home_source' => '4e du TOP 14',
                'away_source' => '5e du TOP 14',
                'home_placeholder' => 'En attente du 4e du TOP 14',
                'away_placeholder' => 'En attente du 5e du TOP 14',
                'description' => '4e TOP 14 contre 5e TOP 14',
                'is_complete' => $position4 !== null && $position5 !== null,
            ],
            [
                'position' => 2,
                'label' => 'Barrage 2',
                'home' => $position3,
                'away' => $position6,
                'home_label' => 'Équipe 1',
                'away_label' => 'Équipe 2',
                'home_source' => '3e du TOP 14',
                'away_source' => '6e du TOP 14',
                'home_placeholder' => 'En attente du 3e du TOP 14',
                'away_placeholder' => 'En attente du 6e du TOP 14',
                'description' => '3e TOP 14 contre 6e TOP 14',
                'is_complete' => $position3 !== null && $position6 !== null,
            ],
        ];

        $completeCount = collect($pairs)
            ->where('is_complete', true)
            ->count();

        $message = match ($completeCount) {
            2 => 'Les deux barrages peuvent être créés automatiquement à partir du classement TOP 14.',
            1 => 'Un barrage est déjà complet et peut être créé automatiquement. Le second reste en attente des positions manquantes.',
            default => 'Les positions nécessaires aux barrages ne sont pas encore suffisamment certaines.',
        };

        return [
            'title' => 'Barrages TOP 14',
            'message' => $message,
            'pairs' => $pairs,
        ];
    }

    private function accessMatchAutomaticSetup(Season $season): array
    {
        $prod2FinalLoser = $this->loserOfMatch(
            $season,
            'prod2_final',
            1
        );

        $top14Barragiste = $this->certifiedTop14ClubAtPosition(
            $season,
            13
        );

        $isComplete = $prod2FinalLoser !== null
            && $top14Barragiste !== null;

        if ($isComplete) {
            $message = 'Les deux équipes de l’access match sont connues. Le match peut être créé automatiquement.';
        } elseif ($top14Barragiste) {
            $message = 'Le 13e du TOP 14 est connu et alimente l’équipe 2. Il reste à connaître le perdant de la finale PRO D2 pour l’équipe 1.';
        } elseif ($prod2FinalLoser) {
            $message = 'Le perdant de la finale PRO D2 est connu et alimente l’équipe 1. Il reste à connaître le 13e du TOP 14 pour l’équipe 2.';
        } else {
            $message = 'Il faut encore connaître le perdant de la finale PRO D2 et le 13e du TOP 14.';
        }

        return [
            'title' => 'Access match TOP 14 / PRO D2',
            'message' => $message,
            'pairs' => [
                [
                    'position' => 1,
                    'label' => 'Access match',
                    'home' => $prod2FinalLoser,
                    'away' => $top14Barragiste,
                    'home_label' => 'Équipe 1',
                    'away_label' => 'Équipe 2',
                    'home_source' => 'Perdant de la finale PRO D2',
                    'away_source' => '13e du TOP 14',
                    'home_placeholder' => 'En attente du perdant de la finale PRO D2',
                    'away_placeholder' => 'En attente du 13e du TOP 14',
                    'description' => 'Perdant finale PRO D2 contre 13e TOP 14',
                    'is_complete' => $isComplete,
                ],
            ],
        ];
    }

    private function top14SemifinalAutomaticSetup(Season $season): array
    {
        $position1 = $this->certifiedTop14ClubAtPosition($season, 1);
        $position2 = $this->certifiedTop14ClubAtPosition($season, 2);
        $playoff1Winner = $this->winnerOfMatch($season, 'top14_playoff', 1);
        $playoff2Winner = $this->winnerOfMatch($season, 'top14_playoff', 2);

        $pairs = [
            [
                'position' => 1,
                'label' => 'Demi-finale 1',
                'home' => $position1,
                'away' => $playoff1Winner,
                'home_label' => 'Équipe 1',
                'away_label' => 'Équipe 2',
                'home_source' => '1er du TOP 14',
                'away_source' => 'Vainqueur du barrage 1',
                'home_placeholder' => 'En attente du 1er du TOP 14',
                'away_placeholder' => 'En attente du vainqueur du barrage 1',
                'description' => '1er TOP 14 contre vainqueur barrage 1',
                'is_complete' => $position1 !== null && $playoff1Winner !== null,
            ],
            [
                'position' => 2,
                'label' => 'Demi-finale 2',
                'home' => $position2,
                'away' => $playoff2Winner,
                'home_label' => 'Équipe 1',
                'away_label' => 'Équipe 2',
                'home_source' => '2e du TOP 14',
                'away_source' => 'Vainqueur du barrage 2',
                'home_placeholder' => 'En attente du 2e du TOP 14',
                'away_placeholder' => 'En attente du vainqueur du barrage 2',
                'description' => '2e TOP 14 contre vainqueur barrage 2',
                'is_complete' => $position2 !== null && $playoff2Winner !== null,
            ],
        ];

        $completeCount = collect($pairs)
            ->where('is_complete', true)
            ->count();

        $message = match ($completeCount) {
            2 => 'Les deux demi-finales peuvent être créées automatiquement.',
            1 => 'Une demi-finale est déjà complète et peut être créée automatiquement. L’autre reste en attente du second barrage.',
            default => 'Les demi-finales attendent encore les vainqueurs des barrages TOP 14.',
        };

        return [
            'title' => 'Demi-finales TOP 14',
            'message' => $message,
            'pairs' => $pairs,
        ];
    }

    private function top14FinalAutomaticSetup(Season $season): array
    {
        $semifinal1Winner = $this->winnerOfMatch(
            $season,
            'top14_semifinal',
            1
        );

        $semifinal2Winner = $this->winnerOfMatch(
            $season,
            'top14_semifinal',
            2
        );

        $isComplete = $semifinal1Winner !== null
            && $semifinal2Winner !== null;

        if ($isComplete) {
            $message = 'La finale peut être créée automatiquement.';
        } elseif ($semifinal1Winner || $semifinal2Winner) {
            $message = 'Un finaliste est déjà connu. Il reste à connaître le vainqueur de l’autre demi-finale.';
        } else {
            $message = 'Il faut encore connaître les vainqueurs des deux demi-finales TOP 14.';
        }

        return [
            'title' => 'Finale TOP 14',
            'message' => $message,
            'pairs' => [
                [
                    'position' => 1,
                    'label' => 'Finale TOP 14',
                    'home' => $semifinal1Winner,
                    'away' => $semifinal2Winner,
                    'home_label' => 'Équipe 1',
                    'away_label' => 'Équipe 2',
                    'home_source' => 'Vainqueur de la demi-finale 1',
                    'away_source' => 'Vainqueur de la demi-finale 2',
                    'home_placeholder' => 'En attente du vainqueur de la demi-finale 1',
                    'away_placeholder' => 'En attente du vainqueur de la demi-finale 2',
                    'description' => 'Vainqueur demi-finale 1 contre vainqueur demi-finale 2',
                    'is_complete' => $isComplete,
                ],
            ],
        ];
    }

    private function accessMatchEligibleClubs(Season $season): Collection
    {
        $knownParticipants = collect([
            $this->loserOfMatch($season, 'prod2_final', 1),
            $this->certifiedTop14ClubAtPosition($season, 13),
        ])
            ->filter()
            ->unique('id')
            ->values();

        if ($knownParticipants->isNotEmpty()) {
            return $knownParticipants;
        }

        return $this->seasonClubs($season);
    }

    private function top14PlayoffEligibleClubs(Season $season): Collection
    {
        return collect([
            $this->certifiedTop14ClubAtPosition($season, 3),
            $this->certifiedTop14ClubAtPosition($season, 4),
            $this->certifiedTop14ClubAtPosition($season, 5),
            $this->certifiedTop14ClubAtPosition($season, 6),
        ])
            ->filter()
            ->unique('id')
            ->values();
    }

    private function top14SemifinalEligibleClubs(Season $season): Collection
    {
        return collect([
            $this->certifiedTop14ClubAtPosition($season, 1),
            $this->certifiedTop14ClubAtPosition($season, 2),
            $this->winnerOfMatch($season, 'top14_playoff', 1),
            $this->winnerOfMatch($season, 'top14_playoff', 2),
        ])
            ->filter()
            ->unique('id')
            ->values();
    }

    private function top14FinalEligibleClubs(Season $season): Collection
    {
        return collect([
            $this->winnerOfMatch($season, 'top14_semifinal', 1),
            $this->winnerOfMatch($season, 'top14_semifinal', 2),
        ])
            ->filter()
            ->unique('id')
            ->values();
    }

    private function certifiedTop14ClubAtPosition(
        Season $season,
        int $position
    ): ?Club {
        $journees = $this->regularJourneesThroughTarget(
            $season,
            self::TARGET_REGULAR_JOURNEE
        );

        $standings = $this->standingsUntilTargetJournee(
            $season,
            self::TARGET_REGULAR_JOURNEE
        );

        $row = $standings
            ->values()
            ->get($position - 1);

        if (! $row) {
            return null;
        }

        if (
            $this->regularJourneesAreComplete(
                $journees,
                self::TARGET_REGULAR_JOURNEE
            )
        ) {
            return $row['club'];
        }

        $above = $standings->take($position - 1);
        $below = $standings->slice($position);

        if (
            $above->contains(
                fn (array $aboveRow) => (int) $aboveRow['points']
                    <= (int) $row['max_points']
            )
        ) {
            return null;
        }

        if (
            $below->contains(
                fn (array $belowRow) => (int) $belowRow['max_points']
                    >= (int) $row['points']
            )
        ) {
            return null;
        }

        return $row['club'];
    }

    private function winnerOfMatch(
        Season $season,
        string $journeeType,
        int $matchPosition
    ): ?Club {
        $match = $this->matchByTypeAndPosition(
            $season,
            $journeeType,
            $matchPosition
        );

        if (! $match || blank($match->actual_result)) {
            return null;
        }

        return match (strtolower((string) $match->actual_result)) {
            'v' => $match->homeClub,
            'd' => $match->awayClub,
            default => null,
        };
    }

    private function loserOfMatch(
        Season $season,
        string $journeeType,
        int $matchPosition
    ): ?Club {
        $match = $this->matchByTypeAndPosition(
            $season,
            $journeeType,
            $matchPosition
        );

        if (! $match || blank($match->actual_result)) {
            return null;
        }

        return match (strtolower((string) $match->actual_result)) {
            'v' => $match->awayClub,
            'd' => $match->homeClub,
            default => null,
        };
    }

    private function matchByTypeAndPosition(
        Season $season,
        string $journeeType,
        int $matchPosition
    ): ?MatchGame {
        $journee = $season->journees()
            ->where('type', $journeeType)
            ->with([
                'matches.homeClub',
                'matches.awayClub',
            ])
            ->orderBy('number')
            ->first();

        if (! $journee) {
            return null;
        }

        $matches = $journee->matches
            ->sortBy('position')
            ->values();

        $positionedMatch = $matches->first(
            fn (MatchGame $match) => (int) $match->position === $matchPosition
        );

        if ($positionedMatch) {
            return $positionedMatch;
        }

        /*
         * Compatibilité avec d’anciennes données éventuellement créées
         * avant l’utilisation du champ position. Dès qu’un match possède
         * une position explicite, on ne retombe pas sur l’index de la
         * collection : cela évite de confondre le match 1 et le match 2
         * lorsqu’ils sont créés séparément ou dans le désordre.
         */
        $hasExplicitPositions = $matches->contains(
            fn (MatchGame $match) => $match->position !== null
        );

        if ($hasExplicitPositions) {
            return null;
        }

        return $matches->get($matchPosition - 1);
    }

    private function regularJourneesThroughTarget(
        Season $season,
        int $targetJourneeNumber
    ): Collection {
        $journees = $season->journees()
            ->where('type', 'regular')
            ->whereBetween('number', [1, $targetJourneeNumber])
            ->with('matches')
            ->orderBy('number')
            ->get();

        $journees->each(function (Journee $journee) use ($season) {
            $journee->setRelation('season', $season);
        });

        return $journees;
    }

    private function regularJourneesAreComplete(
        Collection $journees,
        int $targetJourneeNumber
    ): bool {
        $numbers = $journees
            ->pluck('number')
            ->map(fn ($number) => (int) $number)
            ->unique()
            ->values();

        for ($number = 1; $number <= $targetJourneeNumber; $number++) {
            if (! $numbers->contains($number)) {
                return false;
            }
        }

        foreach ($journees as $journee) {
            $expectedMatchesCount = $journee->expectedMatchesCount();

            if ($expectedMatchesCount === null) {
                return false;
            }

            if ($journee->matches->count() < $expectedMatchesCount) {
                return false;
            }

            if (
                $journee->matches->contains(
                    fn (MatchGame $match) => blank($match->actual_result)
                )
            ) {
                return false;
            }
        }

        return true;
    }

    private function standingsUntilTargetJournee(
        Season $season,
        int $targetJourneeNumber
    ): Collection {
        $clubs = $this->top14Clubs($season);

        $rowsByClubId = [];

        foreach ($clubs as $club) {
            $rowsByClubId[$club->id] = [
                'club' => $club,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'offensive_bonus' => 0,
                'defensive_bonus' => 0,
                'bonus_total' => 0,
                'points' => 0,
                'remaining_matches' => $targetJourneeNumber,
                'max_points' => self::MAX_POINTS_PER_REMAINING_MATCH
                    * $targetJourneeNumber,
            ];
        }

        $journees = $this->regularJourneesThroughTarget(
            $season,
            $targetJourneeNumber
        );

        foreach ($journees as $journee) {
            foreach ($journee->matches as $match) {
                if (blank($match->actual_result)) {
                    continue;
                }

                if (
                    ! isset(
                        $rowsByClubId[$match->home_club_id],
                        $rowsByClubId[$match->away_club_id]
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

        foreach ($rowsByClubId as &$row) {
            $row['remaining_matches'] = max(
                0,
                $targetJourneeNumber - (int) $row['played']
            );

            $row['max_points'] = (int) $row['points']
                + (
                    $row['remaining_matches']
                    * self::MAX_POINTS_PER_REMAINING_MATCH
                );
        }

        unset($row);

        return collect($rowsByClubId)
            ->sort(
                fn (array $a, array $b) => $this->compareStandingRows($a, $b)
            )
            ->values();
    }

    private function applyMatchToStandings(
        array &$rowsByClubId,
        MatchGame $match
    ): void {
        $homeClubId = (int) $match->home_club_id;
        $awayClubId = (int) $match->away_club_id;

        $rowsByClubId[$homeClubId]['played']++;
        $rowsByClubId[$awayClubId]['played']++;

        $result = strtolower((string) $match->actual_result);

        if ($result === 'v') {
            $rowsByClubId[$homeClubId]['won']++;
            $rowsByClubId[$homeClubId]['points'] += 4;

            $rowsByClubId[$awayClubId]['lost']++;
        } elseif ($result === 'd') {
            $rowsByClubId[$awayClubId]['won']++;
            $rowsByClubId[$awayClubId]['points'] += 4;

            $rowsByClubId[$homeClubId]['lost']++;
        } elseif ($result === 'n') {
            $rowsByClubId[$homeClubId]['drawn']++;
            $rowsByClubId[$homeClubId]['points'] += 2;

            $rowsByClubId[$awayClubId]['drawn']++;
            $rowsByClubId[$awayClubId]['points'] += 2;
        }

        $this->applyBonusToStanding(
            $rowsByClubId[$homeClubId],
            $match->actual_home_bonus
        );

        $this->applyBonusToStanding(
            $rowsByClubId[$awayClubId],
            $match->actual_away_bonus
        );
    }

    private function applyBonusToStanding(
        array &$row,
        ?string $bonus
    ): void {
        $bonus = $this->normalizeBonus($bonus);

        if ($bonus === null) {
            return;
        }

        if ($bonus === 'o') {
            $row['offensive_bonus']++;
            $row['bonus_total']++;
            $row['points']++;

            return;
        }

        if ($bonus === 'd') {
            $row['defensive_bonus']++;
            $row['bonus_total']++;
            $row['points']++;
        }
    }

    private function normalizeBonus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['o', 'd'], true)
            ? $value
            : null;
    }

    private function compareStandingRows(
        array $a,
        array $b
    ): int {
        $comparison = $b['points'] <=> $a['points'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $b['won'] <=> $a['won'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $b['drawn'] <=> $a['drawn'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $b['bonus_total'] <=> $a['bonus_total'];

        if ($comparison !== 0) {
            return $comparison;
        }

        $comparison = $a['lost'] <=> $b['lost'];

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

    private function top14Clubs(Season $season): Collection
    {
        return $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get();
    }

    private function prod2Clubs(Season $season): Collection
    {
        return $season->clubs()
            ->wherePivot('competition', 'prod2')
            ->orderBy('name')
            ->get();
    }

    private function seasonClubs(Season $season): Collection
    {
        return $season->clubs()
            ->orderBy('name')
            ->get();
    }

    private function emptyAutomaticSetup(): array
    {
        return [
            'title' => null,
            'message' => null,
            'pairs' => [],
        ];
    }
}
