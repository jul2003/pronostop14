<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\Season;
use App\Models\SeasonPreseasonQuestion;
use App\Services\PreseasonAutoResultService;
use App\Services\PreseasonScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreseasonAutoResultController extends Controller
{
    public function store(
        Request $request,
        Season $season,
        string $question,
        PreseasonAutoResultService $autoResultService,
        PreseasonScoringService $preseasonScoringService
    ) {
        $questionModel = $this->resolveQuestionForSeason($season, $question);

        if (! $questionModel) {
            return $this->redirectAfterAction($season, $request)
                ->with('error', 'La question avant-saison concernée est introuvable pour cette saison.');
        }

        if ($season->is_locked) {
            return $this->redirectAfterAction($season, $request)
                ->with('error', 'Cette saison est verrouillée : le résultat avant-saison ne peut pas être modifié.');
        }

        $data = $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'source_journee_id' => ['nullable', 'integer', 'exists:journees,id'],
        ]);

        $suggestion = $autoResultService->suggestionForQuestion($season, $questionModel);

        if (! $suggestion || (int) $suggestion['club_id'] !== (int) $data['club_id']) {
            return $this->redirectAfterAction($season, $request)
                ->with('error', 'Ce résultat automatique n’est plus certain. Le résultat avant-saison n’a pas été mémorisé.');
        }

        DB::transaction(function () use ($questionModel, $data, $season, $preseasonScoringService) {
            $questionModel->update([
                'result_club_id' => $data['club_id'],
                'result_text_answer' => null,
                'result_recorded_at' => now(),
            ]);

            $preseasonScoringService->recalculateSeason($season);
        });

        $remainingSuggestions = $autoResultService->suggestionsForSeason($season)
            ->values()
            ->all();

        $redirect = $this->redirectAfterAction($season, $request)
            ->with('success', 'Résultat avant-saison mémorisé et points recalculés : '.$suggestion['question_label'].' → '.$suggestion['club_name'].'.');

        if (! empty($remainingSuggestions)) {
            $redirect->with('preseason_auto_result_suggestions', $remainingSuggestions);
        }

        return $redirect;
    }

    private function resolveQuestionForSeason(Season $season, string $questionId): ?SeasonPreseasonQuestion
    {
        if (! ctype_digit($questionId)) {
            return null;
        }

        return $season->preseasonQuestions()
            ->whereKey((int) $questionId)
            ->first();
    }

    private function redirectAfterAction(Season $season, Request $request)
    {
        $sourceJourneeId = $request->input('source_journee_id');

        if ($sourceJourneeId) {
            $sourceJournee = $season->journees()
                ->whereKey($sourceJourneeId)
                ->first();

            if ($sourceJournee instanceof Journee) {
                return redirect()->route('admin.seasons.journees.results', [$season, $sourceJournee]);
            }
        }

        return redirect()->route('admin.seasons.preseason-results.edit', $season);
    }
}
