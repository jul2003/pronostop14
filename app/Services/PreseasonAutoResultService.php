<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Journee;
use App\Models\MatchGame;
use App\Models\Season;
use App\Models\SeasonPreseasonQuestion;
use Illuminate\Support\Collection;

class PreseasonAutoResultService
{
    private const MAX_POINTS_PER_REMAINING_MATCH = 5;

    public function suggestionsAfterJourneeResultsSaved(Season $season, Journee $journee): array
    {
        return $this->suggestionsForSeason($season)
            ->values()
            ->all();
    }

    public function suggestionsForSeason(Season $season): Collection
    {
        $questions = $season->preseasonQuestions()
            ->with('resultClub')
            ->where('is_active', true)
            ->whereNotNull('auto_result_rule')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return $questions
            ->map(fn (SeasonPreseasonQuestion $question) => $this->suggestionForQuestion($season, $question))
            ->filter()
            ->values();
    }

    public function suggestionForQuestion(Season $season, SeasonPreseasonQuestion $question): ?array
    {
        if ((int) $question->season_id !== (int) $season->id) {
            return null;
        }

        if (! $question->is_active) {
            return null;
        }

        if (blank($question->auto_result_rule)) {
            return null;
        }

        if (! array_key_exists($question->auto_result_rule, SeasonPreseasonQuestion::autoResultRuleOptions())) {
            return null;
        }

        if (! $question->supportsAutoResult()) {
            return null;
        }

        $targetJourneeNumber = $question->auto_result_journee_number
            ? (int) $question->auto_result_journee_number
            : null;

        $autoResultPosition = $question->auto_result_position
            ? (int) $question->auto_result_position
            : null;

        if (SeasonPreseasonQuestion::autoResultRuleRequiresJourneeNumber($question->auto_result_rule)) {
            if ($targetJourneeNumber === null || $targetJourneeNumber < 1 || $targetJourneeNumber > 26) {
                return null;
            }
        }

        if (SeasonPreseasonQuestion::autoResultRuleRequiresPosition($question->auto_result_rule)) {
            if ($autoResultPosition === null || $autoResultPosition < 1 || $autoResultPosition > 14) {
                return null;
            }
        }

        $resolution = match ($question->auto_result_rule) {
            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_POSITION => $this->detectTop14Position(
                $season,
                $targetJourneeNumber,
                $autoResultPosition
            ),

            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_PLAYOFF_1_WINNER => $this->detectSpecialMatchWinner(
                $season,
                'top14_playoff',
                'barrage TOP 14 1',
                1
            ),

            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_PLAYOFF_2_WINNER => $this->detectSpecialMatchWinner(
                $season,
                'top14_playoff',
                'barrage TOP 14 2',
                2
            ),

            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_FINAL_WINNER => $this->detectSpecialMatchWinner(
                $season,
                'top14_final',
                'finale TOP 14'
            ),

            SeasonPreseasonQuestion::AUTO_RESULT_RULE_PROD2_FINAL_WINNER => $this->detectSpecialMatchWinner(
                $season,
                'prod2_final',
                'finale PRO D2'
            ),

            SeasonPreseasonQuestion::AUTO_RESULT_RULE_ACCESS_MATCH_WINNER => $this->detectSpecialMatchWinner(
                $season,
                'access_match',
                'access match TOP 14 / PRO D2'
            ),

            default => null,
        };

        if (! $resolution) {
            return null;
        }

        /** @var Club $club */
        $club = $resolution['club'];

        if (! $this->clubIsCompatibleWithQuestion($season, $question, $club)) {
            return null;
        }

        if (
            $question->result_club_id
            && (int) $question->result_club_id === (int) $club->id
        ) {
            return null;
        }

        $existingClub = $question->resultClub;

        return [
            'question_id' => $question->id,
            'question_label' => $question->label,
            'rule' => $question->auto_result_rule,
            'rule_label' => $question->autoResultRuleLabel(),
            'target_journee_number' => $targetJourneeNumber,
            'auto_result_position' => $autoResultPosition,
            'club_id' => $club->id,
            'club_name' => $club->name,
            'club_logo_url' => $club->logo_url,
            'points' => $resolution['points'] ?? null,
            'max_other_points' => $resolution['max_other_points'] ?? null,
            'min_other_points' => $resolution['min_other_points'] ?? null,
            'existing_club_id' => $existingClub?->id,
            'existing_club_name' => $existingClub?->name,
            'is_replacement' => $existingClub !== null,
            'explanation' => $resolution['explanation'],
        ];
    }

    private function detectTop14Position(
        Season $season,
        int $targetJourneeNumber,
        int $position
    ): ?array {
        /*
         * Quand toutes les journées jusqu’à la journée cible sont terminées,
         * le classement final est utilisé directement. Cela permet notamment
         * de conserver les départages lorsque plusieurs clubs ont le même
         * nombre de points.
         */
        $completedPosition = $this->detectTop14PositionAfterCompleteTarget(
            $season,
            $targetJourneeNumber,
            $position
        );

        if ($completedPosition) {
            return $completedPosition;
        }

        /*
         * Avant la fin de la journée cible, on vérifie désormais toutes les
         * positions, et plus uniquement la 1re et la 14e.
         */
        return $this->detectGuaranteedTop14Position(
            $season,
            $targetJourneeNumber,
            $position
        );
    }

    private function detectGuaranteedTop14Position(
        Season $season,
        int $targetJourneeNumber,
        int $position
    ): ?array {
        $standings = $this->standingsUntilTargetJournee(
            $season,
            $targetJourneeNumber
        );

        if ($standings->count() < 2) {
            return null;
        }

        $row = $standings
            ->values()
            ->get($position - 1);

        if (! $row) {
            return null;
        }

        /*
         * Clubs actuellement placés devant et derrière le club étudié.
         */
        $above = $standings->take($position - 1);
        $below = $standings->slice($position);

        /*
         * Pour que le club soit définitivement à cette position, aucun club
         * actuellement devant ne doit pouvoir être rejoint.
         *
         * Si un club devant possède un total actuel inférieur ou égal au
         * maximum possible du club étudié, celui-ci pourrait encore le
         * dépasser ou l’égaler : la position n’est donc pas certaine.
         */
        if (
            $above->contains(
                fn (array $aboveRow) => (int) $aboveRow['points']
                    <= (int) $row['max_points']
            )
        ) {
            return null;
        }

        /*
         * Aucun club actuellement derrière ne doit pouvoir rejoindre le
         * total actuel du club étudié.
         */
        if (
            $below->contains(
                fn (array $belowRow) => (int) $belowRow['max_points']
                    >= (int) $row['points']
            )
        ) {
            return null;
        }

        $positionLabel = $position === 1
            ? '1er'
            : $position.'e';

        $explanationParts = [];

        if ($above->isNotEmpty()) {
            $minAbovePoints = (int) $above->min('points');
            $aboveCount = $above->count();

            $explanationParts[] = $aboveCount
                .' club'
                .($aboveCount > 1 ? 's ont' : ' a')
                .' déjà au moins '
                .$minAbovePoints
                .' point(s), soit plus que son maximum possible de '
                .$row['max_points']
                .' point(s)';
        }

        if ($below->isNotEmpty()) {
            $maxBelowPoints = (int) $below->max('max_points');
            $belowCount = $below->count();

            $explanationParts[] = $belowCount
                .' club'
                .($belowCount > 1 ? 's ne peuvent' : ' ne peut')
                .' plus atteindre ses '
                .$row['points']
                .' point(s), avec un maximum possible de '
                .$maxBelowPoints
                .' point(s)';
        }

        $resolution = [
            'club' => $row['club'],
            'points' => (int) $row['points'],

            'explanation' => $row['club']->name
                .' est mathématiquement certain d’être '
                .$positionLabel
                .' du TOP 14 à la J'
                .$targetJourneeNumber
                .' : '
                .implode(', et ', $explanationParts)
                .'.',
        ];

        /*
         * Conservation des informations historiquement renvoyées pour le
         * premier et le dernier, afin de ne pas modifier le format attendu
         * par les vues ou les tests existants.
         */
        if ($position === 1) {
            $resolution['max_other_points'] = (int) $below->max('max_points');
        }

        if ($position === $standings->count()) {
            $resolution['min_other_points'] = (int) $above->min('points');
        }

        return $resolution;
    }

    private function detectTop14PositionAfterCompleteTarget(
        Season $season,
        int $targetJourneeNumber,
        int $position
    ): ?array {
        $journees = $this->regularJourneesThroughTarget(
            $season,
            $targetJourneeNumber
        );

        if (! $this->regularJourneesAreComplete($journees, $targetJourneeNumber)) {
            return null;
        }

        $standings = $this->standingsUntilTargetJournee(
            $season,
            $targetJourneeNumber
        );

        $row = $standings
            ->values()
            ->get($position - 1);

        if (! $row) {
            return null;
        }

        $positionLabel = $position === 1
            ? '1er'
            : $position.'e';

        return [
            'club' => $row['club'],
            'points' => (int) $row['points'],

            'explanation' => $row['club']->name
                .' est classé '
                .$positionLabel
                .' du TOP 14 après la J'
                .$targetJourneeNumber
                .' avec '
                .$row['points']
                .' point(s).',
        ];
    }

    private function detectSpecialMatchWinner(
        Season $season,
        string $journeeType,
        string $journeeLabel,
        ?int $matchPosition = null
    ): ?array {
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

        $match = $matchPosition
            ? (
                $matches->firstWhere('position', $matchPosition)
                ?: $matches->get($matchPosition - 1)
            )
            : $matches->first();

        if (! $match || blank($match->actual_result)) {
            return null;
        }

        $actualResult = strtolower((string) $match->actual_result);

        if ($actualResult === 'v') {
            $club = $match->homeClub;
        } elseif ($actualResult === 'd') {
            $club = $match->awayClub;
        } else {
            return null;
        }

        if (! $club) {
            return null;
        }

        return [
            'club' => $club,
            'points' => null,
            'explanation' => $club->name
                .' est le vainqueur du match « '
                .$journeeLabel
                .' ».',
        ];
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
        $clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
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

    private function clubIsCompatibleWithQuestion(
        Season $season,
        SeasonPreseasonQuestion $question,
        Club $club
    ): bool {
        if ($question->answer_type === 'season_club') {
            return $season->clubs()
                ->where('clubs.id', $club->id)
                ->exists();
        }

        if ($question->answer_type === 'top14_club') {
            return $season->clubs()
                ->where('clubs.id', $club->id)
                ->wherePivot('competition', 'top14')
                ->exists();
        }

        if ($question->answer_type === 'prod2_club') {
            return $season->clubs()
                ->where('clubs.id', $club->id)
                ->wherePivot('competition', 'prod2')
                ->exists();
        }

        return false;
    }
}
