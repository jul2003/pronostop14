<?php

namespace App\Http\Controllers;

use App\Models\Journee;
use App\Models\JourneeUserScore;
use App\Models\Season;
use App\Models\SeasonPreseasonUserBonusScore;
use App\Services\PreseasonDeadlineService;

class RankingController extends Controller
{
    public function general(Season $season)
    {
        $scores = $season->players()
            ->with(['journeeScores' => function ($query) use ($season) {
                $query->whereHas('journee', function ($query) use ($season) {
                    $query->where('season_id', $season->id);
                });
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user,
                    'total_points' => $user->journeeScores->sum('total_points'),
                ];
            })
            ->sortByDesc('total_points')
            ->values();

        $rank = 0;
        $position = 0;
        $previousPoints = null;

        $scores = $scores->map(function ($score) use (&$rank, &$position, &$previousPoints) {
            $position++;

            if ($previousPoints !== $score['total_points']) {
                $rank = $position;
            }

            $score['rank'] = $rank;
            $previousPoints = $score['total_points'];

            return $score;
        });

        return view('rankings.general', [
            'season' => $season,
            'scores' => $scores,
        ]);
    }

    public function journee(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if (! $journee->isLocked()) {
            abort(403, 'Le classement de cette journée sera visible après la clôture des pronostics.');
        }

        $playerIds = $season->players()
            ->pluck('users.id');

        $scores = JourneeUserScore::with('user')
            ->where('journee_id', $journee->id)
            ->whereIn('user_id', $playerIds)
            ->orderBy('rank')
            ->orderByDesc('total_points')
            ->get();

        return view('rankings.journee', [
            'season' => $season,
            'journee' => $journee,
            'scores' => $scores,
        ]);
    }

    public function journeeResults(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        if (! $journee->isLocked()) {
            abort(403, 'Les résultats de cette journée ne sont pas encore visibles.');
        }

        $matches = $journee->matches()
            ->with(['homeClub', 'awayClub', 'pronos.user'])
            ->orderBy('position')
            ->get();

        $players = $season->players()
            ->get();

        return view('journees.results', [
            'season' => $season,
            'journee' => $journee,
            'matches' => $matches,
            'players' => $players,
        ]);
    }

    public function seasonResults(Season $season, PreseasonDeadlineService $preseasonDeadlineService)
    {
        $players = $season->players()
            ->get();

        $preseasonDeadline = $preseasonDeadlineService->deadlineForUser($season, auth()->user());
        $preseasonIsVisible = $preseasonDeadline
            ? $preseasonDeadlineService->isLockedForUser($season, auth()->user())
            : false;

        $preseasonQuestions = collect();
        $preseasonBonusRules = collect();
        $preseasonBonusScores = collect();

        if ($preseasonIsVisible) {
            $preseasonQuestions = $season->preseasonQuestions()
                ->where('is_active', true)
                ->with([
                    'resultClub',
                    'predictions.user',
                    'predictions.club',
                ])
                ->orderBy('position')
                ->get();

            $preseasonBonusRules = $season->preseasonBonusRules()
                ->where('is_active', true)
                ->orderBy('position')
                ->get();

            $preseasonBonusScores = SeasonPreseasonUserBonusScore::with(['user', 'bonusRule'])
                ->where('season_id', $season->id)
                ->get()
                ->groupBy('season_preseason_bonus_rule_id');
        }

        $journees = $season->journees()
            ->where('type', '!=', 'preseason')
            ->with([
                'matches.homeClub',
                'matches.awayClub',
                'matches.pronos.user',
            ])
            ->withCount('matches')
            ->orderByRaw("
                CASE
                    WHEN type = 'regular' THEN number
                    WHEN type = 'prod2_final' THEN 100
                    WHEN type = 'access_match' THEN 101
                    WHEN type = 'top14_playoff' THEN 102
                    WHEN type = 'top14_semifinal' THEN 103
                    WHEN type = 'top14_final' THEN 104
                    ELSE 999
                END
            ")
            ->get()
            ->filter(fn (Journee $journee) => $journee->isLocked())
            ->values();

        return view('seasons.results', [
            'season' => $season,
            'players' => $players,
            'journees' => $journees,
            'preseasonDeadline' => $preseasonDeadline,
            'preseasonIsVisible' => $preseasonIsVisible,
            'preseasonQuestions' => $preseasonQuestions,
            'preseasonBonusRules' => $preseasonBonusRules,
            'preseasonBonusScores' => $preseasonBonusScores,
        ]);
    }
}
