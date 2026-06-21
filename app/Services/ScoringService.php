<?php

namespace App\Services;

use App\Models\Journee;
use App\Models\JourneeUserScore;
use App\Models\MatchGame;
use App\Models\Prono;
use App\Models\SeasonScoringProfile;
use App\Models\User;

class ScoringService
{
    public function calculateMatchPoints(Prono $prono, MatchGame $match): int
    {
        $match->loadMissing([
            'journee.season.scoringRules',
            'journee.season.journeeTypeScoringProfiles.profile.rules',
        ]);

        $profile = $this->profileForMatch($match);
        $rules = $this->rulesForMatch($match);

        if (! $match->actual_result) {
            return 0;
        }

        $resultIsCorrect = $prono->predicted_result === $match->actual_result;

        if (! $resultIsCorrect && ($profile?->stop_on_wrong_result ?? true)) {
            return 0;
        }

        $points = 0;

        if ($resultIsCorrect) {
            $points += match ($match->actual_result) {
                'v' => $rules['home_win'] ?? 0,
                'd' => $rules['away_win'] ?? 0,
                'n' => $rules['draw'] ?? 0,
                default => 0,
            };
        }

        if ($match->actual_tries !== null && $prono->predicted_tries !== null) {
            $difference = abs($prono->predicted_tries - $match->actual_tries);

            if ($difference === 0) {
                $points += $rules['tries_exact'] ?? 0;
            } elseif ($difference === 1) {
                $points += $rules['tries_near'] ?? 0;
            }
        }

        $points += $this->calculateBonusPoints(
            $prono->predicted_home_bonus,
            $match->actual_home_bonus,
            $rules
        );

        $points += $this->calculateBonusPoints(
            $prono->predicted_away_bonus,
            $match->actual_away_bonus,
            $rules
        );

        return $points;
    }

    public function updateJourneeUserScore(User $user, Journee $journee): JourneeUserScore
    {
        $matchPoints = Prono::where('user_id', $user->id)
            ->whereHas('matchGame', function ($query) use ($journee) {
                $query->where('journee_id', $journee->id);
            })
            ->sum('points');

        $perfectJourneeBonus = $this->calculatePerfectJourneeBonus($user, $journee);

        return JourneeUserScore::updateOrCreate(
            [
                'journee_id' => $journee->id,
                'user_id' => $user->id,
            ],
            [
                'match_points' => $matchPoints,
                'perfect_journee_bonus' => $perfectJourneeBonus,
                'total_points' => $matchPoints + $perfectJourneeBonus,
            ]
        );
    }

    public function updateJourneeRanking(Journee $journee): void
    {
        $scores = JourneeUserScore::where('journee_id', $journee->id)
            ->orderByDesc('total_points')
            ->orderBy('user_id')
            ->get();

        $rank = 0;
        $position = 0;
        $previousPoints = null;

        foreach ($scores as $score) {
            $position++;

            if ($previousPoints !== $score->total_points) {
                $rank = $position;
            }

            $score->update([
                'rank' => $rank,
            ]);

            $previousPoints = $score->total_points;
        }
    }

    public function calculatePerfectJourneeBonus(User $user, Journee $journee): int
    {
        $matches = $journee->matches()
            ->whereNotNull('actual_result')
            ->get();

        if ($matches->isEmpty()) {
            return 0;
        }

        foreach ($matches as $match) {
            $prono = Prono::where('user_id', $user->id)
                ->where('match_game_id', $match->id)
                ->first();

            if (! $prono) {
                return 0;
            }

            if ($prono->predicted_result !== $match->actual_result) {
                return 0;
            }
        }

        $rules = $this->rulesForJournee($journee);

        return $rules['perfect_round'] ?? 0;
    }

    private function calculateBonusPoints(?string $predictedBonus, ?string $actualBonus, array $rules): int
    {
        if ($predictedBonus === null || $predictedBonus === '') {
            return 0;
        }

        if ($actualBonus === null || $actualBonus === '') {
            return 0;
        }

        if ($predictedBonus === $actualBonus) {
            return $rules['bonus_correct'] ?? 0;
        }

        return $rules['bonus_wrong'] ?? 0;
    }

    private function profileForMatch(MatchGame $match): ?SeasonScoringProfile
    {
        return $this->profileForJournee($match->journee);
    }

    private function profileForJournee(Journee $journee): ?SeasonScoringProfile
    {
        $journee->loadMissing([
            'season.journeeTypeScoringProfiles.profile.rules',
        ]);

        $mapping = $journee->season
            ->journeeTypeScoringProfiles
            ->firstWhere('journee_type', $journee->type);

        return $mapping?->profile;
    }

    private function rulesForMatch(MatchGame $match): array
    {
        return $this->rulesForJournee($match->journee);
    }

    private function rulesForJournee(Journee $journee): array
    {
        $journee->loadMissing([
            'season.scoringRules',
            'season.journeeTypeScoringProfiles.profile.rules',
        ]);

        $profile = $this->profileForJournee($journee);

        if ($profile) {
            return $profile->rules
                ->pluck('points', 'code')
                ->toArray();
        }

        return $journee->season
            ->scoringRules
            ->pluck('points', 'code')
            ->toArray();
    }
}
