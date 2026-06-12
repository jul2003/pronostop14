<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\Request;

class SeasonScoringRuleController extends Controller
{
    public function edit(Season $season)
    {
        if (! $season->scoringRules()->exists()) {
            $this->createDefaultScoringRules($season);
        }

        $rules = $season->scoringRules()
            ->orderBy('position')
            ->get();

        return view('admin.seasons.scoring', [
            'season' => $season,
            'rules' => $rules,
        ]);
    }

    public function update(Request $request, Season $season)
    {
        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.points' => ['required', 'integer'],
        ]);

        foreach ($data['rules'] as $ruleId => $ruleData) {
            $season->scoringRules()
                ->where('id', $ruleId)
                ->update([
                    'points' => $ruleData['points'],
                ]);
        }

        return redirect()
            ->route('admin.seasons.scoring.edit', $season)
            ->with('success', 'Barème enregistré.');
    }

    private function createDefaultScoringRules(Season $season): void
        {
            $rules = [
                ['home_win', 'Résultat juste — victoire domicile', 2, 1],
                ['away_win', 'Résultat juste — victoire extérieur', 5, 2],
                ['draw', 'Résultat juste — match nul', 10, 3],
                ['tries_exact', 'Nombre d’essais exact', 2, 4],
                ['tries_near', 'Nombre d’essais à +/- 1', 1, 5],
                ['bonus_correct', 'Bonus pronostiqué juste', 2, 6],
                ['bonus_wrong', 'Bonus pronostiqué faux', -1, 7],
                ['perfect_round', 'Bonus journée parfaite', 3, 8],
            ];

            foreach ($rules as [$code, $label, $points, $position]) {
                $season->scoringRules()->create([
                    'code' => $code,
                    'label' => $label,
                    'points' => $points,
                    'position' => $position,
                ]);
            }
        }
}
