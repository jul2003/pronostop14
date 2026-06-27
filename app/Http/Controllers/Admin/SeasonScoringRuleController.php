<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonScoringProfile;
use Illuminate\Http\Request;

class SeasonScoringRuleController extends Controller
{
    public function edit(Season $season)
    {
        $profiles = SeasonScoringProfile::where('season_id', $season->id)
            ->with([
                'rules' => function ($query) {
                    $query->orderBy('position');
                },
            ])
            ->orderBy('position')
            ->get();

        return view('admin.seasons.scoring', [
            'season' => $season,
            'profiles' => $profiles,
        ]);
    }

    public function update(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.scoring.edit', $season)
                ->with('error', 'Cette saison est verrouillée : le barème ne peut plus être modifié.');
        }

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
}
