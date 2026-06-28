<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonPreseasonQuestion;
use App\Services\PreseasonScoringService;
use Illuminate\Http\Request;

class SeasonPreseasonResultController extends Controller
{
    public function edit(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->with('resultClub')
            ->orderBy('position')
            ->get();

        $top14Clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get();

        $prod2Clubs = $season->clubs()
            ->wherePivot('competition', 'prod2')
            ->orderBy('name')
            ->get();

        $seasonClubs = $season->clubs()
            ->orderBy('name')
            ->get();

        return view('admin.seasons.preseason-results', [
            'season' => $season,
            'questions' => $questions,
            'top14Clubs' => $top14Clubs,
            'prod2Clubs' => $prod2Clubs,
            'seasonClubs' => $seasonClubs,
        ]);
    }

    public function update(
        Request $request,
        Season $season,
        PreseasonScoringService $scoringService
    ) {
        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.preseason-results.edit', $season)
                ->with('error', 'Cette saison est verrouillée : les résultats avant-saison ne peuvent plus être modifiés.');
        }

        $data = $request->validate([
            'results' => ['nullable', 'array'],
            'results.*.club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'results.*.text_answer' => ['nullable', 'string', 'max:255'],
            'lock_season' => ['nullable', 'boolean'],
        ]);

        $questions = $season->preseasonQuestions()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        foreach ($questions as $question) {
            $result = $data['results'][$question->id] ?? [];

            if ($question->answer_type === 'free_text') {
                $textAnswer = $result['text_answer'] ?? null;

                $question->update([
                    'result_club_id' => null,
                    'result_text_answer' => filled($textAnswer) ? $textAnswer : null,
                    'result_recorded_at' => filled($textAnswer) ? now() : null,
                ]);

                continue;
            }

            $clubId = $result['club_id'] ?? null;

            if ($clubId !== null && $clubId !== '') {
                $this->validateClubAnswer($season, $question, (int) $clubId);
            }

            $question->update([
                'result_club_id' => filled($clubId) ? (int) $clubId : null,
                'result_text_answer' => null,
                'result_recorded_at' => filled($clubId) ? now() : null,
            ]);
        }

        $scoringService->recalculateSeason($season);

        if ($request->boolean('lock_season')) {
            $season->update([
                'is_locked' => true,
            ]);

            return redirect()
                ->route('admin.seasons.preseason-results.edit', $season)
                ->with('success', 'Résultats avant-saison enregistrés, points recalculés et saison verrouillée.');
        }

        return redirect()
            ->route('admin.seasons.preseason-results.edit', $season)
            ->with('success', 'Résultats avant-saison enregistrés et points recalculés.');
    }

    private function validateClubAnswer(
        Season $season,
        SeasonPreseasonQuestion $question,
        int $clubId
    ): void {
        $query = $season->clubs()
            ->where('clubs.id', $clubId);

        if ($question->answer_type === 'top14_club') {
            $query->wherePivot('competition', 'top14');
        }

        if ($question->answer_type === 'prod2_club') {
            $query->wherePivot('competition', 'prod2');
        }

        if (! $query->exists()) {
            abort(422, 'Club invalide pour cette question.');
        }
    }

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
