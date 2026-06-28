<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Season;
use App\Models\User;
use App\Services\GlobalSetupHashService;
use App\Services\PreseasonDeadlineService;
use App\Services\SeasonJourneeGenerator;
use App\Services\SeasonPreseasonSetupService;
use App\Services\SeasonScoringSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::withCount('journees')
            ->orderBy('name')
            ->get();

        return view('admin.seasons.index', compact('seasons'));
    }

    public function create()
    {
        return view('admin.seasons.create');
    }

    public function store(
        Request $request,
        SeasonPreseasonSetupService $preseasonSetup,
        SeasonScoringSetupService $scoringSetup,
        GlobalSetupHashService $hashService
    ) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:seasons,name'],
            'is_active' => ['nullable', 'boolean'],
            'top14_clubs_count' => ['required', 'integer', 'min:2'],
            'prod2_clubs_count' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $data, $preseasonSetup, $scoringSetup, $hashService) {
            $previousActiveSeason = Season::where('is_active', true)->first();

            $journeeHash = $hashService->journeeScoringHash();
            $preseasonHash = $hashService->preseasonHash();

            if ($request->boolean('is_active')) {
                Season::query()->update([
                    'is_active' => false,
                ]);
            }

            $season = Season::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'is_active' => $request->boolean('is_active'),
                'is_locked' => false,
                'top14_clubs_count' => $data['top14_clubs_count'],
                'prod2_clubs_count' => $data['prod2_clubs_count'],
                'journee_scoring_setup_hash' => $journeeHash,
                'preseason_setup_hash' => $preseasonHash,
            ]);

            if (
                $previousActiveSeason &&
                $previousActiveSeason->journee_scoring_setup_hash === $journeeHash
            ) {
                $scoringSetup->copyFromSeason($previousActiveSeason, $season);
            } else {
                $scoringSetup->copyJourneeScoringProfilesToSeason($season);
            }

            if (
                $previousActiveSeason &&
                $previousActiveSeason->preseason_setup_hash === $preseasonHash
            ) {
                $preseasonSetup->copyFromSeason($previousActiveSeason, $season);
            } else {
                $preseasonSetup->copyTemplatesToSeason($season);
            }

            if ($previousActiveSeason) {
                $clubsToSync = [];

                foreach ($previousActiveSeason->clubs as $club) {
                    $clubsToSync[$club->id] = [
                        'competition' => $club->pivot->competition,
                    ];
                }

                $season->clubs()->sync($clubsToSync);

                $season->players()->sync(
                    $previousActiveSeason->players
                        ->pluck('id')
                        ->toArray()
                );
            }
        });

        return redirect()
            ->route('admin.seasons.index')
            ->with('success', 'Saison créée.');
    }

    public function clubs(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $clubs = Club::orderBy('name')->get();

        $selectedTop14 = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->pluck('clubs.id')
            ->toArray();

        $selectedProd2 = $season->clubs()
            ->wherePivot('competition', 'prod2')
            ->pluck('clubs.id')
            ->toArray();

        return view('admin.seasons.clubs', [
            'season' => $season,
            'clubs' => $clubs,
            'selectedTop14' => $selectedTop14,
            'selectedProd2' => $selectedProd2,
        ]);
    }

    public function syncClubs(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.clubs', $season)
                ->with('error', 'Cette saison est verrouillée : les clubs ne peuvent plus être modifiés.');
        }

        $data = $request->validate([
            'top14_clubs' => ['nullable', 'array'],
            'top14_clubs.*' => ['integer', 'exists:clubs,id'],
            'prod2_clubs' => ['nullable', 'array'],
            'prod2_clubs.*' => ['integer', 'exists:clubs,id'],
        ]);

        $top14Count = count($data['top14_clubs'] ?? []);
        $prod2Count = count($data['prod2_clubs'] ?? []);

        if ($top14Count > $season->top14_clubs_count) {
            return back()->withErrors([
                'top14_clubs' => "Tu ne peux pas sélectionner plus de {$season->top14_clubs_count} clubs TOP 14.",
            ]);
        }

        if ($prod2Count > $season->prod2_clubs_count) {
            return back()->withErrors([
                'prod2_clubs' => "Tu ne peux pas sélectionner plus de {$season->prod2_clubs_count} clubs PRO D2.",
            ]);
        }

        $syncData = [];

        foreach ($data['top14_clubs'] ?? [] as $clubId) {
            $syncData[$clubId] = [
                'competition' => 'top14',
            ];
        }

        foreach ($data['prod2_clubs'] ?? [] as $clubId) {
            $syncData[$clubId] = [
                'competition' => 'prod2',
            ];
        }

        $season->clubs()->sync($syncData);

        if ($request->filled('redirect_after_save')) {
            return redirect()
                ->to($request->input('redirect_after_save'))
                ->with('success', 'Clubs participants enregistrés.');
        }

        return redirect()
            ->route('admin.seasons.clubs', $season)
            ->with('success', 'Clubs participants enregistrés.');
    }

    public function edit(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        return view('admin.seasons.edit', compact('season'));
    }

    public function update(Request $request, Season $season)
    {
        if ($request->boolean('unlock_season')) {
            if (! $season->is_locked) {
                return redirect()
                    ->route('admin.seasons.edit', $season)
                    ->with('error', 'Cette saison est déjà déverrouillée.');
            }

            $season->update([
                'is_locked' => false,
            ]);

            return redirect()
                ->route('admin.seasons.edit', $season)
                ->with('success', 'Saison déverrouillée.');
        }

        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.edit', $season)
                ->with('error', 'Cette saison est verrouillée : seule l’action de déverrouillage est autorisée.');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('seasons', 'name')->ignore($season->id),
            ],
            'top14_clubs_count' => ['required', 'integer', 'min:2'],
            'prod2_clubs_count' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_active')) {
            Season::where('id', '!=', $season->id)->update([
                'is_active' => false,
            ]);
        }

        $season->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'top14_clubs_count' => $data['top14_clubs_count'],
            'prod2_clubs_count' => $data['prod2_clubs_count'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.seasons.index')
            ->with('success', 'Saison modifiée.');
    }

    public function destroy(Season $season)
    {
        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.index')
                ->with('error', 'Cette saison est verrouillée : elle ne peut pas être supprimée.');
        }

        $season->delete();

        return redirect()
            ->route('admin.seasons.index')
            ->with('success', 'Saison supprimée.');
    }

    public function generateJournees(Season $season, SeasonJourneeGenerator $generator)
    {
        if ($season->is_locked) {
            return back()->withErrors([
                'season' => 'Cette saison est verrouillée : les journées ne peuvent plus être générées.',
            ]);
        }

        $top14Count = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->count();

        if ($top14Count !== $season->top14_clubs_count) {
            return back()->withErrors([
                'clubs' => "Impossible de générer les journées : {$top14Count} club(s) TOP 14 sélectionné(s) sur {$season->top14_clubs_count}.",
            ]);
        }

        if ($season->journees()->exists()) {
            return back()->withErrors([
                'journees' => 'Les journées ont déjà été générées pour cette saison.',
            ]);
        }

        $generator->generate($season);

        return back()->with('success', 'Journées générées.');
    }

    public function players(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $users = User::whereIn('role', ['player', 'admin', 'super_admin'])
            ->orderBy('nickname')
            ->orderBy('name')
            ->get();

        $selectedPlayers = $season->players()
            ->pluck('users.id')
            ->toArray();

        return view('admin.seasons.players', [
            'season' => $season,
            'users' => $users,
            'selectedPlayers' => $selectedPlayers,
        ]);
    }

    public function syncPlayers(
        Request $request,
        Season $season,
        PreseasonDeadlineService $preseasonDeadlineService
    ) {
        if ($season->is_locked) {
            return redirect()
                ->route('admin.seasons.players', $season)
                ->with('error', 'Cette saison est verrouillée : les joueurs ne peuvent plus être modifiés.');
        }

        $data = $request->validate([
            'players' => ['nullable', 'array'],
            'players.*' => ['integer', 'exists:users,id'],
        ]);

        $currentPlayers = $season->players()
            ->get()
            ->keyBy('id');

        $selectedPlayerIds = $data['players'] ?? [];

        $newPlayerDeadline = $preseasonDeadlineService->deadlineForNewParticipant($season);

        $syncData = [];

        foreach ($selectedPlayerIds as $index => $userId) {
            $currentPlayer = $currentPlayers->get($userId);

            $syncData[$userId] = [
                'display_order' => $currentPlayer?->pivot?->display_order ?? $index + 1,
                'preseason_prediction_deadline' => $currentPlayer?->pivot?->preseason_prediction_deadline
                    ?: $newPlayerDeadline?->format('Y-m-d H:i:s'),
            ];
        }

        $season->players()->sync($syncData);

        return redirect()
            ->route('admin.seasons.players', $season)
            ->with('success', 'Joueurs de la saison enregistrés.');
    }

    public function reorderPlayers(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Cette saison est verrouillée : les joueurs ne peuvent plus être réordonnés.',
            ], 403);
        }

        $data = $request->validate([
            'players' => ['required', 'array'],
            'players.*' => ['integer', 'exists:users,id'],
        ]);

        foreach ($data['players'] as $index => $userId) {
            $season->players()->updateExistingPivot($userId, [
                'display_order' => $index + 1,
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
