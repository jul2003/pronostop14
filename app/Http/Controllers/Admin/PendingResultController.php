<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\AppDateService;

class PendingResultController extends Controller
{
    public function index(AppDateService $dateService)
    {
        $season = Season::where('is_active', true)
            ->with([
                'journees' => function ($query) use ($dateService) {
                    $query->where('type', '!=', 'preseason')
                        ->whereNotNull('prediction_deadline')
                        ->where('prediction_deadline', '<=', $dateService->now())
                        ->withCount([
                            'matches',
                            'matches as finished_matches_count' => function ($query) {
                                $query->where('is_finished', true);
                            },
                        ])
                        ->orderBy('number');
                },
            ])
            ->first();

        $journees = collect();

        if ($season) {
            $journees = $season->journees
                ->filter(function ($journee) {
                    $expectedMatchesCount = $journee->expectedMatchesCount();

                    if ($expectedMatchesCount === null) {
                        return false;
                    }

                    if ((int) $journee->matches_count < $expectedMatchesCount) {
                        return true;
                    }

                    return (int) $journee->finished_matches_count < $expectedMatchesCount;
                })
                ->values();
        }

        return view('admin.pending-results.index', [
            'season' => $season,
            'journees' => $journees,
        ]);
    }
}
