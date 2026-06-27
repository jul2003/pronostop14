<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonPreseasonQuestion;
use App\Services\AppDateService;

class PendingResultController extends Controller
{
    public function index(AppDateService $dateService)
    {
        $now = $dateService->now();

        $season = Season::where('is_active', true)
            ->with([
                'journees' => function ($query) use ($now) {
                    $query->where('type', '!=', 'preseason')
                        ->whereNotNull('prediction_deadline')
                        ->where('prediction_deadline', '<=', $now)
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

        $preseasonJournee = null;
        $preseasonQuestionsCount = 0;
        $preseasonResultsCount = 0;
        $preseasonNeedsResults = false;

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

            $preseasonJournee = $season->journees()
                ->where('type', 'preseason')
                ->whereNotNull('prediction_deadline')
                ->where('prediction_deadline', '<=', $now)
                ->first();

            if ($preseasonJournee) {
                $preseasonQuestions = SeasonPreseasonQuestion::where('season_id', $season->id)
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->get();

                $preseasonQuestionsCount = $preseasonQuestions->count();

                $preseasonResultsCount = $preseasonQuestions
                    ->filter(function (SeasonPreseasonQuestion $question) {
                        return $question->hasOfficialResult();
                    })
                    ->count();

                $preseasonNeedsResults = $preseasonQuestionsCount > 0
                    && $preseasonResultsCount < $preseasonQuestionsCount;
            }
        }

        return view('admin.pending-results.index', [
            'season' => $season,
            'journees' => $journees,
            'preseasonJournee' => $preseasonJournee,
            'preseasonQuestionsCount' => $preseasonQuestionsCount,
            'preseasonResultsCount' => $preseasonResultsCount,
            'preseasonNeedsResults' => $preseasonNeedsResults,
        ]);
    }
}
