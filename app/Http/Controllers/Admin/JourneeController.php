<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journee;
use App\Models\Season;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JourneeController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.seasons.index');
    }

    public function season(Season $season)
    {
        $journees = $season->journees()
            ->orderByRaw("
                CASE
                    WHEN type = 'preseason' THEN 0
                    WHEN type = 'regular' THEN number
                    WHEN type = 'prod2_final' THEN 100
                    WHEN type = 'access_match' THEN 101
                    WHEN type = 'top14_playoff' THEN 102
                    WHEN type = 'top14_semifinal' THEN 103
                    WHEN type = 'top14_final' THEN 104
                    ELSE 999
                END
            ")
            ->get();

        return view('admin.journees.season', [
            'season' => $season,
            'journees' => $journees,
        ]);
    }

    public function edit(Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        return view('admin.journees.edit', [
            'season' => $season,
            'journee' => $journee,
        ]);
    }

    public function update(Request $request, Season $season, Journee $journee)
    {
        abort_if($journee->season_id !== $season->id, 404);

        $data = $request->validate([
            'prediction_deadline' => ['nullable', 'date'],
        ]);

        $journee->update([
            'prediction_deadline' => $request->filled('prediction_deadline')
                ? Carbon::parse($request->prediction_deadline)->endOfDay()
                : null,
        ]);

        return redirect()
            ->route('admin.seasons.journees', $season)
            ->with('success', 'Date limite mise à jour.');
    }
}
