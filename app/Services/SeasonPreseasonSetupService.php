<?php

namespace App\Services;

use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\Season;
use App\Models\SeasonPreseasonBonusRule;
use App\Models\SeasonPreseasonQuestion;
use Illuminate\Support\Facades\DB;

class SeasonPreseasonSetupService
{
    public function copyTemplatesToSeason(Season $season): void
    {
        DB::transaction(function () use ($season) {
            if ($season->preseasonQuestions()->exists()) {
                return;
            }

            $templateToSeasonQuestionIds = [];

            $templates = PreseasonPredictionTemplate::with('profile.rules')
                ->where('is_active', true)
                ->orderBy('position')
                ->get();

            foreach ($templates as $template) {
                $points = (int) optional(
                    $template->profile?->rules->firstWhere('code', 'correct')
                )->points;

                $question = SeasonPreseasonQuestion::create([
                    'season_id' => $season->id,
                    'source_template_id' => $template->id,
                    'scoring_profile_id' => $template->scoring_profile_id,
                    'label' => $template->label,
                    'answer_type' => $template->answer_type,
                    'correction_group' => $template->correction_group,
                    'correction_mode' => $template->correction_mode,
                    'points' => $points,
                    'position' => $template->position,
                    'is_active' => $template->is_active,
                ]);

                $templateToSeasonQuestionIds[$template->id] = $question->id;
            }

            $bonusTemplates = PreseasonBonusRuleTemplate::with('questions')
                ->where('is_active', true)
                ->orderBy('position')
                ->get();

            foreach ($bonusTemplates as $bonusTemplate) {
                $bonusRule = SeasonPreseasonBonusRule::create([
                    'season_id' => $season->id,
                    'source_template_id' => $bonusTemplate->id,
                    'label' => $bonusTemplate->label,
                    'points' => $bonusTemplate->points,
                    'position' => $bonusTemplate->position,
                    'is_active' => $bonusTemplate->is_active,
                    'stop_after_match' => $bonusTemplate->stop_after_match,
                ]);

                $seasonQuestionIds = $bonusTemplate->questions
                    ->pluck('id')
                    ->map(fn ($templateQuestionId) => $templateToSeasonQuestionIds[$templateQuestionId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $bonusRule->questions()->sync($seasonQuestionIds);
            }
        });
    }

    public function copyFromSeason(Season $sourceSeason, Season $targetSeason): void
    {
        DB::transaction(function () use ($sourceSeason, $targetSeason) {
            if ($targetSeason->preseasonQuestions()->exists()) {
                return;
            }

            $sourceSeason->load([
                'preseasonQuestions',
                'preseasonBonusRules.questions',
            ]);

            $sourceToTargetQuestionIds = [];

            foreach ($sourceSeason->preseasonQuestions as $sourceQuestion) {
                $targetQuestion = SeasonPreseasonQuestion::create([
                    'season_id' => $targetSeason->id,
                    'source_template_id' => $sourceQuestion->source_template_id,
                    'scoring_profile_id' => $sourceQuestion->scoring_profile_id,
                    'label' => $sourceQuestion->label,
                    'answer_type' => $sourceQuestion->answer_type,
                    'correction_group' => $sourceQuestion->correction_group,
                    'correction_mode' => $sourceQuestion->correction_mode,
                    'points' => $sourceQuestion->points,
                    'position' => $sourceQuestion->position,
                    'is_active' => $sourceQuestion->is_active,
                ]);

                $sourceToTargetQuestionIds[$sourceQuestion->id] = $targetQuestion->id;
            }

            foreach ($sourceSeason->preseasonBonusRules as $sourceBonusRule) {
                $targetBonusRule = SeasonPreseasonBonusRule::create([
                    'season_id' => $targetSeason->id,
                    'source_template_id' => $sourceBonusRule->source_template_id,
                    'label' => $sourceBonusRule->label,
                    'points' => $sourceBonusRule->points,
                    'position' => $sourceBonusRule->position,
                    'is_active' => $sourceBonusRule->is_active,
                    'stop_after_match' => $sourceBonusRule->stop_after_match,
                ]);

                $targetQuestionIds = $sourceBonusRule->questions
                    ->pluck('id')
                    ->map(fn ($sourceQuestionId) => $sourceToTargetQuestionIds[$sourceQuestionId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $targetBonusRule->questions()->sync($targetQuestionIds);
            }
        });
    }
}
