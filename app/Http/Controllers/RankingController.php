<?php

namespace App\Http\Controllers;

use App\Models\Journee;
use App\Models\JourneeUserScore;
use App\Models\Season;

class RankingController extends Controller
{
    public function journee(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

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
}
