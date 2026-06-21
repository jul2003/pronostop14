<?php

namespace App\Services;

use App\Models\JourneeTypeScoringProfile;
use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;

class GlobalSetupHashService
{
    public function journeeScoringHash(): string
    {
        $profiles = ScoringProfile::with('rules')
            ->where('category', 'journee')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function ($profile) {
                return [
                    'code' => $profile->code,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'position' => $profile->position,
                    'stop_on_wrong_result' => $profile->stop_on_wrong_result,
                    'rules' => $profile->rules
                        ->sortBy([
                            ['position', 'asc'],
                            ['id', 'asc'],
                        ])
                        ->map(fn ($rule) => [
                            'code' => $rule->code,
                            'label' => $rule->label,
                            'points' => $rule->points,
                            'position' => $rule->position,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $mappings = JourneeTypeScoringProfile::with('profile')
            ->orderBy('journee_type')
            ->get()
            ->map(fn ($mapping) => [
                'journee_type' => $mapping->journee_type,
                'profile_code' => $mapping->profile?->code,
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'profiles' => $profiles,
            'mappings' => $mappings,
        ]));
    }

    public function preseasonHash(): string
    {
        $questions = PreseasonPredictionTemplate::with('profile')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($template) => [
                'label' => $template->label,
                'answer_type' => $template->answer_type,
                'profile_code' => $template->profile?->code,
                'position' => $template->position,
                'is_active' => $template->is_active,
            ])
            ->values()
            ->all();

        $bonusRules = PreseasonBonusRuleTemplate::with('questions')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn ($bonusRule) => [
                'label' => $bonusRule->label,
                'points' => $bonusRule->points,
                'position' => $bonusRule->position,
                'is_active' => $bonusRule->is_active,
                'stop_after_match' => $bonusRule->stop_after_match,
                'question_positions' => $bonusRule->questions
                    ->sortBy('position')
                    ->pluck('position')
                    ->values()
                    ->all(),
                'question_labels' => $bonusRule->questions
                    ->sortBy('position')
                    ->pluck('label')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'questions' => $questions,
            'bonus_rules' => $bonusRules,
        ]));
    }
}
