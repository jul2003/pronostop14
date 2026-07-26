<?php

namespace App\Http\Controllers;

use App\Models\Season;

class SeasonRuleController extends Controller
{
    public function index()
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with('error', 'Aucune saison active pour le moment.');
        }

        return redirect()->route('season-rules.season', $season);
    }

    public function season(Season $season)
    {
        $season->load([
            'scoringRules',
            'journeeTypeScoringProfiles.profile.rules',
        ]);

        $seasons = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $journeeTypesInSeason = $season->journees()
            ->where('type', '!=', 'preseason')
            ->reorder()
            ->pluck('type')
            ->filter()
            ->unique()
            ->values();

        $orderedTypes = collect([
            'regular',
            'top14_playoff',
            'access_match',
            'top14_semifinal',
            'prod2_final',
            'top14_final',
        ])->filter(fn ($type) => $journeeTypesInSeason->contains($type));

        $journeeRuleBlocks = $orderedTypes
            ->map(function ($journeeType) use ($season) {
                $mapping = $season->journeeTypeScoringProfiles
                    ->firstWhere('journee_type', $journeeType);

                $profile = $mapping?->profile;

                $rules = $profile
                    ? $profile->rules->pluck('points', 'code')->toArray()
                    : $season->scoringRules->pluck('points', 'code')->toArray();

                return [
                    'type' => $journeeType,
                    'label' => $this->journeeTypeLabel($journeeType),
                    'profile_name' => $profile?->name ?? 'Barème principal de la saison',
                    'stop_on_wrong_result' => (bool) ($profile?->stop_on_wrong_result ?? true),
                    'rules' => $rules,
                ];
            })
            ->values();

        $preseasonQuestions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $preseasonBonusRules = $season->preseasonBonusRules()
            ->where('is_active', true)
            ->with(['questions' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('position');
            }])
            ->orderBy('position')
            ->get();

        return view('season-rules.index', [
            'seasons' => $seasons,
            'selectedSeason' => $season,
            'journeeRuleBlocks' => $journeeRuleBlocks,
            'preseasonQuestions' => $preseasonQuestions,
            'preseasonBonusRules' => $preseasonBonusRules,
        ]);
    }

    private function journeeTypeLabel(string $type): string
    {
        return match ($type) {
            'regular' => 'Journées régulières',
            'top14_playoff' => 'Barrages TOP 14',
            'access_match' => 'Access match',
            'top14_semifinal' => 'Demi-finales TOP 14',
            'prod2_final' => 'Finale PRO D2',
            'top14_final' => 'Finale TOP 14',
            default => $type,
        };
    }
}
