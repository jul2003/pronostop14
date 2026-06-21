<?php

namespace Database\Seeders;

use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonPredictionTemplate;
use Illuminate\Database\Seeder;

class PreseasonBonusRuleTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $questions = PreseasonPredictionTemplate::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $allQuestionIds = $questions->pluck('id')->values()->all();

        $top14SemiQuestionIds = $questions
            ->where('label', 'Demi-finaliste TOP 14')
            ->pluck('id')
            ->values()
            ->all();

        $prod2SemiQuestionIds = $questions
            ->where('label', 'Demi-finaliste PRO D2')
            ->pluck('id')
            ->values()
            ->all();

        $bonusRules = [
            [
                'label' => 'Perfect avant-saison',
                'points' => 275,
                'position' => 10,
                'is_active' => true,
                'stop_after_match' => true,
                'question_ids' => $allQuestionIds,
            ],
            [
                'label' => '4 demi-finalistes TOP 14',
                'points' => 60,
                'position' => 20,
                'is_active' => true,
                'stop_after_match' => false,
                'question_ids' => $top14SemiQuestionIds,
            ],
            [
                'label' => '4 demi-finalistes PRO D2',
                'points' => 60,
                'position' => 30,
                'is_active' => true,
                'stop_after_match' => false,
                'question_ids' => $prod2SemiQuestionIds,
            ],
        ];

        foreach ($bonusRules as $bonusRuleData) {
            $questionIds = $bonusRuleData['question_ids'];

            unset($bonusRuleData['question_ids']);

            $bonusRule = PreseasonBonusRuleTemplate::updateOrCreate(
                ['label' => $bonusRuleData['label']],
                $bonusRuleData
            );

            $bonusRule->questions()->sync($questionIds);
        }
    }
}
