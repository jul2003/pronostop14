<?php

namespace App\Services;

use App\Models\Season;
use App\Models\SeasonPreseasonPrediction;
use App\Models\SeasonPreseasonQuestion;
use App\Models\SeasonPreseasonUserBonusScore;
use App\Models\User;
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
            $this->recalculateQuestionPrediction($season, $user, $question);
        }

        $this->recalculateUserBonuses($season, $user);
    }

    private function recalculateQuestionPrediction(
        Season $season,
        User $user,
        SeasonPreseasonQuestion $question
    ): void {
        $prediction = SeasonPreseasonPrediction::where('season_id', $season->id)
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->first();

        if (! $prediction) {
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

    private function normalizeText(?string $value): string
    {
        return Str::of($value ?? '')
            ->trim()
            ->lower()
            ->toString();
    }
}
