<?php

namespace App\Services;

use App\Models\Season;
use App\Models\SeasonPreseasonPrediction;
use App\Models\SeasonPreseasonQuestion;
use App\Models\SeasonPreseasonUserBonusScore;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PreseasonScoringService
{
    public function recalculateSeason(Season $season): void
    {
        DB::transaction(function () use ($season) {
            $season->load([
                'players',
                'preseasonQuestions',
                'preseasonBonusRules.questions',
            ]);

            foreach ($season->players as $player) {
                $this->recalculateUser($season, $player);
            }
        });
    }

    public function recalculateUser(Season $season, User $user): void
    {
        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        foreach ($questions as $question) {
            $this->recalculateQuestionPrediction($season, $user, $question, $questions);
        }

        $this->recalculateUserBonuses($season, $user);
    }

    private function recalculateQuestionPrediction(
        Season $season,
        User $user,
        SeasonPreseasonQuestion $question,
        Collection $questions
    ): void {
        $prediction = SeasonPreseasonPrediction::where('season_id', $season->id)
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        if (! $prediction) {
            return;
        }

        $group = $this->questionResultGroup($question);

        if ($group) {
            $this->recalculateGroupedClubPrediction(
                $prediction,
                $question,
                $questions,
                $group
            );

            return;
        }

        if (! $question->hasOfficialResult()) {
            $prediction->update([
                'is_correct' => null,
                'points' => 0,
            ]);

            return;
        }

        $isCorrect = $this->predictionIsCorrect($prediction, $question);

        $prediction->update([
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? (int) $question->points : 0,
        ]);
    }

    private function recalculateGroupedClubPrediction(
        SeasonPreseasonPrediction $prediction,
        SeasonPreseasonQuestion $question,
        Collection $questions,
        string $group
    ): void {
        if ($prediction->club_id === null) {
            $prediction->update([
                'is_correct' => null,
                'points' => 0,
            ]);

            return;
        }

        $groupQuestions = $questions
            ->filter(function (SeasonPreseasonQuestion $candidate) use ($group) {
                return $this->questionResultGroup($candidate) === $group;
            })
            ->values();

        $officialClubIds = $groupQuestions
            ->filter(function (SeasonPreseasonQuestion $candidate) {
                return $candidate->hasOfficialResult()
                    && $candidate->result_club_id !== null;
            })
            ->pluck('result_club_id')
            ->map(fn ($clubId) => (int) $clubId)
            ->unique()
            ->values();

        if ($officialClubIds->isEmpty()) {
            $prediction->update([
                'is_correct' => null,
                'points' => 0,
            ]);

            return;
        }

        $isCorrect = $officialClubIds->contains((int) $prediction->club_id);

        if ($isCorrect) {
            $prediction->update([
                'is_correct' => true,
                'points' => (int) $question->points,
            ]);

            return;
        }

        $groupResultsAreComplete = $officialClubIds->count() >= $groupQuestions->count();

        $prediction->update([
            'is_correct' => $groupResultsAreComplete ? false : null,
            'points' => 0,
        ]);
    }

    private function predictionIsCorrect(
        SeasonPreseasonPrediction $prediction,
        SeasonPreseasonQuestion $question
    ): bool {
        if ($question->answer_type === 'free_text') {
            return $this->normalizeText($prediction->text_answer)
                === $this->normalizeText($question->result_text_answer);
        }

        return $prediction->club_id !== null
            && (int) $prediction->club_id === (int) $question->result_club_id;
    }

    private function recalculateUserBonuses(Season $season, User $user): void
    {
        $bonusRules = $season->preseasonBonusRules()
            ->where('is_active', true)
            ->with('questions')
            ->orderBy('position')
            ->get();

        foreach ($bonusRules as $bonusRule) {
            $questions = $bonusRule->questions
                ->filter(fn ($question) => $question->is_active)
                ->values();

            $isAwarded = false;

            if ($questions->isNotEmpty()) {
                $isAwarded = $questions->every(function ($question) use ($season, $user) {
                    if (! $question->hasOfficialResult()) {
                        return false;
                    }

                    $prediction = SeasonPreseasonPrediction::where('season_id', $season->id)
                        ->where('user_id', $user->id)
                        ->where('question_id', $question->id)
                        ->first();

                    return $prediction?->is_correct === true;
                });
            }

            SeasonPreseasonUserBonusScore::updateOrCreate(
                [
                    'season_id' => $season->id,
                    'user_id' => $user->id,
                    'season_preseason_bonus_rule_id' => $bonusRule->id,
                ],
                [
                    'is_awarded' => $isAwarded,
                    'points' => $isAwarded ? (int) $bonusRule->points : 0,
                ]
            );
        }
    }

    private function questionResultGroup(SeasonPreseasonQuestion $question): ?string
    {
        $label = $this->normalizeLabel($question->label);

        if (str_contains($label, 'demi') && str_contains($label, 'top 14')) {
            return 'top14_semifinalists';
        }

        if (str_contains($label, 'demi') && str_contains($label, 'pro d2')) {
            return 'prod2_semifinalists';
        }

        return null;
    }

    private function normalizeLabel(?string $value): string
    {
        return Str::of(Str::ascii($value ?? ''))
            ->trim()
            ->lower()
            ->toString();
    }

    private function normalizeText(?string $value): string
    {
        return Str::of($value ?? '')
            ->trim()
            ->lower()
            ->toString();
    }
}
