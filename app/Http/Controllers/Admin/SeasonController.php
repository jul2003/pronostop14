<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Season;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Club;
use App\Services\SeasonJourneeGenerator;
use App\Models\User;

class SeasonController extends Controller
{
    //
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:seasons,name'],
            'is_active' => ['nullable', 'boolean'],
            'top14_clubs_count' => ['required', 'integer', 'min:2'],
            'prod2_clubs_count' => ['required', 'integer', 'min:0'],
        ]);

        $previousActiveSeason = Season::where('is_active', true)->first();

        if ($request->boolean('is_active')) {
            Season::query()->update([
                'is_active' => false,
            ]);
        }

        $season = Season::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active'),
            'top14_clubs_count' => $data['top14_clubs_count'],
            'prod2_clubs_count' => $data['prod2_clubs_count'],
        ]);

        $this->createDefaultScoringRules($season);

        if ($previousActiveSeason) {

            // Copie des clubs
            $clubsToSync = [];

            foreach ($previousActiveSeason->clubs as $club) {
                $clubsToSync[$club->id] = [
                    'competition' => $club->pivot->competition,
                ];
            }

            $season->clubs()->sync($clubsToSync);

            // Copie des joueurs
            $season->players()->sync(
                $previousActiveSeason->players
                    ->pluck('id')
                    ->toArray()
            );
        }

        return redirect()
            ->route('admin.seasons.index')
            ->with('success', 'Saison créée.');
    }

    public function clubs(Season $season)
    {
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

    public function edit(Season $season)
    {
        return view('admin.seasons.edit', compact('season'));
    }

    public function update(Request $request, Season $season)
    {
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
        $season->delete();

        return redirect()
            ->route('admin.seasons.index')
            ->with('success', 'Saison supprimée.');
    }

    public function generateJournees(Season $season, SeasonJourneeGenerator $generator)
    {
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

    public function players(Season $season)
    {
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

    public function syncPlayers(Request $request, Season $season)
    {
        $data = $request->validate([
            'players' => ['nullable', 'array'],
            'players.*' => ['integer', 'exists:users,id'],
        ]);

        $season->players()->sync($data['players'] ?? []);

        return redirect()
            ->route('admin.seasons.players', $season)
            ->with('success', 'Joueurs de la saison enregistrés.');
    }
}
