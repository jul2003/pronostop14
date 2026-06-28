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

        $groupedQuestionIds = $this->recalculateUnorderedCorrectionGroups(
            $season,
            $user,
            $questions
        );

        foreach ($questions as $question) {
            if ($groupedQuestionIds->contains($question->id)) {
                continue;
            }

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

    private function recalculateUnorderedCorrectionGroups(
        Season $season,
        User $user,
        Collection $questions
    ): Collection {
        $groupedQuestions = $questions
            ->filter(fn (SeasonPreseasonQuestion $question) => $question->usesUnorderedCorrectionGroup())
            ->groupBy('correction_group');

        $processedQuestionIds = collect();

        foreach ($groupedQuestions as $questionsInGroup) {
            $this->recalculateUnorderedCorrectionGroup(
                $season,
                $user,
                $questionsInGroup->values()
            );

            $processedQuestionIds = $processedQuestionIds->merge(
                $questionsInGroup->pluck('id')
            );
        }

        return $processedQuestionIds
            ->unique()
            ->values();
    }

    private function recalculateUnorderedCorrectionGroup(
        Season $season,
        User $user,
        Collection $questionsInGroup
    ): void {
        $questionIds = $questionsInGroup
            ->pluck('id')
            ->values()
            ->all();

        $predictions = SeasonPreseasonPrediction::where('season_id', $season->id)
            ->where('user_id', $user->id)
            ->whereIn('question_id', $questionIds)
            ->get()
            ->keyBy('question_id');

        if ($predictions->isEmpty()) {
            return;
        }

        $officialClubIds = $questionsInGroup
            ->filter(fn (SeasonPreseasonQuestion $question) => $question->result_club_id !== null)
            ->pluck('result_club_id')
            ->map(fn ($clubId) => (int) $clubId)
            ->unique()
            ->values();

        $groupResultsAreComplete = $questionsInGroup->every(
            fn (SeasonPreseasonQuestion $question) => $question->hasOfficialResult()
                && $question->result_club_id !== null
        );

        if ($officialClubIds->isEmpty()) {
            foreach ($predictions as $prediction) {
                $prediction->update([
                    'is_correct' => null,
                    'points' => 0,
                ]);
            }

            return;
        }

        $alreadyAwardedClubIds = collect();

        foreach ($questionsInGroup as $question) {
            $prediction = $predictions->get($question->id);

            if (! $prediction) {
                continue;
            }

            if ($prediction->club_id === null) {
                $prediction->update([
                    'is_correct' => null,
                    'points' => 0,
                ]);

                continue;
            }

            $predictedClubId = (int) $prediction->club_id;

            if (
                $officialClubIds->contains($predictedClubId)
                && ! $alreadyAwardedClubIds->contains($predictedClubId)
            ) {
                $prediction->update([
                    'is_correct' => true,
                    'points' => (int) $question->points,
                ]);

                $alreadyAwardedClubIds->push($predictedClubId);

                continue;
            }

            if ($officialClubIds->contains($predictedClubId)) {
                $prediction->update([
                    'is_correct' => false,
                    'points' => 0,
                ]);

                continue;
            }

            $prediction->update([
                'is_correct' => $groupResultsAreComplete ? false : null,
                'points' => 0,
            ]);
        }
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
