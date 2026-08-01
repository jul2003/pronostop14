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
            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_POSITION => $this->detectTop14Position($season, $targetJourneeNumber, $autoResultPosition),
            SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_FINAL_WINNER => $this->detectSpecialMatchWinner($season, 'top14_final', 'finale TOP 14'),
            SeasonPreseasonQuestion::AUTO_RESULT_RULE_PROD2_FINAL_WINNER => $this->detectSpecialMatchWinner($season, 'prod2_final', 'finale PRO D2'),
            SeasonPreseasonQuestion::AUTO_RESULT_RULE_ACCESS_MATCH_WINNER => $this->detectSpecialMatchWinner($season, 'access_match', 'access match TOP 14 / PRO D2'),
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

        if ($question->result_club_id && (int) $question->result_club_id === (int) $club->id) {
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
        if ($position === 1) {
            return $this->detectGuaranteedLeader($season, $targetJourneeNumber);
        }

        if ($position === 14) {
            return $this->detectGuaranteedLast($season, $targetJourneeNumber);
        }

        return $this->detectTop14PositionAfterCompleteTarget($season, $targetJourneeNumber, $position);
    }

    private function detectGuaranteedLeader(Season $season, int $targetJourneeNumber): ?array
    {
        $standings = $this->standingsUntilTargetJournee($season, $targetJourneeNumber);

        if ($standings->count() < 2) {
            return null;
        }

        foreach ($standings as $row) {
            $maxOtherPoints = $standings
                ->reject(fn (array $otherRow) => (int) $otherRow['club']->id === (int) $row['club']->id)
                ->max('max_points');

            if ($maxOtherPoints === null) {
                continue;
            }

            if ((int) $row['points'] > (int) $maxOtherPoints) {
                return [
                    'club' => $row['club'],
                    'points' => (int) $row['points'],
                    'max_other_points' => (int) $maxOtherPoints,
                    'explanation' => $row['club']->name.' est mathématiquement certain d’être 1er du TOP 14 à la J'.$targetJourneeNumber.' : '.$row['points'].' point(s), aucun autre club ne peut dépasser '.$maxOtherPoints.' point(s).',
                ];
            }
        }

        return null;
    }

    private function detectGuaranteedLast(Season $season, int $targetJourneeNumber): ?array
    {
        $standings = $this->standingsUntilTargetJournee($season, $targetJourneeNumber);

        if ($standings->count() < 2) {
            return null;
        }

        foreach ($standings as $row) {
            $minOtherPoints = $standings
                ->reject(fn (array $otherRow) => (int) $otherRow['club']->id === (int) $row['club']->id)
                ->min('points');

            if ($minOtherPoints === null) {
                continue;
            }

            if ((int) $row['max_points'] < (int) $minOtherPoints) {
                return [
                    'club' => $row['club'],
                    'points' => (int) $row['points'],
                    'min_other_points' => (int) $minOtherPoints,
                    'explanation' => $row['club']->name.' est mathématiquement certain d’être 14e du TOP 14 à la J'.$targetJourneeNumber.' : il peut atteindre au maximum '.$row['max_points'].' point(s), alors que tous les autres clubs ont déjà au moins '.$minOtherPoints.' point(s).',
                ];
            }
        }

        return null;
    }

    private function detectTop14PositionAfterCompleteTarget(
        Season $season,
        int $targetJourneeNumber,
        int $position
    ): ?array {
        $journees = $this->regularJourneesThroughTarget($season, $targetJourneeNumber);

        if (! $this->regularJourneesAreComplete($journees, $targetJourneeNumber)) {
            return null;
        }

        $standings = $this->standingsUntilTargetJournee($season, $targetJourneeNumber);
        $row = $standings->values()->get($position - 1);

        if (! $row) {
            return null;
        }

        return [
            'club' => $row['club'],
            'points' => (int) $row['points'],
            'explanation' => $row['club']->name.' est classé '.$position.'e du TOP 14 après la J'.$targetJourneeNumber.' avec '.$row['points'].' point(s).',
        ];
    }

    private function detectSpecialMatchWinner(
        Season $season,
        string $journeeType,
        string $journeeLabel
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

        $match = $journee->matches
            ->sortBy('position')
            ->first();

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
            'explanation' => $club->name.' est le vainqueur du match « '.$journeeLabel.' ».',
        ];
    }

    private function regularJourneesThroughTarget(Season $season, int $targetJourneeNumber): Collection
    {
        return $season->journees()
            ->where('type', 'regular')
            ->whereBetween('number', [1, $targetJourneeNumber])
            ->with('matches')
            ->orderBy('number')
            ->get();
    }

    private function regularJourneesAreComplete(Collection $journees, int $targetJourneeNumber): bool
    {
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

            if ($journee->matches->contains(fn (MatchGame $match) => blank($match->actual_result))) {
                return false;
            }
        }

        return true;
    }

    private function standingsUntilTargetJournee(Season $season, int $targetJourneeNumber): Collection
    {
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
                'max_points' => self::MAX_POINTS_PER_REMAINING_MATCH * $targetJourneeNumber,
            ];
        }

        $journees = $this->regularJourneesThroughTarget($season, $targetJourneeNumber);

        foreach ($journees as $journee) {
            foreach ($journee->matches as $match) {
                if (blank($match->actual_result)) {
                    continue;
                }

                if (! isset($rowsByClubId[$match->home_club_id], $rowsByClubId[$match->away_club_id])) {
                    continue;
                }

                $this->applyMatchToStandings($rowsByClubId, $match);
            }
        }

        foreach ($rowsByClubId as &$row) {
            $row['remaining_matches'] = max(0, $targetJourneeNumber - (int) $row['played']);
            $row['max_points'] = (int) $row['points'] + ($row['remaining_matches'] * self::MAX_POINTS_PER_REMAINING_MATCH);
        }

        unset($row);

        return collect($rowsByClubId)
            ->sort(fn (array $a, array $b) => $this->compareStandingRows($a, $b))
            ->values();
    }

    private function applyMatchToStandings(array &$rowsByClubId, MatchGame $match): void
    {
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

        $this->applyBonusToStanding($rowsByClubId[$homeClubId], $match->actual_home_bonus);
        $this->applyBonusToStanding($rowsByClubId[$awayClubId], $match->actual_away_bonus);
    }

    private function applyBonusToStanding(array &$row, ?string $bonus): void
    {
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

        return in_array($value, ['o', 'd'], true) ? $value : null;
    }

    private function compareStandingRows(array $a, array $b): int
    {
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

        $comparison = strcasecmp($a['club']->name, $b['club']->name);

        if ($comparison !== 0) {
            return $comparison;
        }

        return (int) $a['club']->id <=> (int) $b['club']->id;
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
