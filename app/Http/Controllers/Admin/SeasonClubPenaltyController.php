<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonClubPenalty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeasonClubPenaltyController extends Controller
{
    public function store(
        Request $request,
        Season $season
    ): RedirectResponse {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'club_id' => [
                'required',
                'integer',
                'exists:clubs,id',
            ],

            'points_deduction' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'effective_from_journee' => [
                'required',
                'integer',
                'min:1',
                'max:26',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (
            ! $this->clubBelongsToTop14(
                $season,
                (int) $data['club_id']
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'club_id' =>
                        'La pénalité doit concerner un club TOP 14 de cette saison.',
                ]);
        }

        $season->clubPenalties()->create([
            'club_id' =>
                (int) $data['club_id'],

            'points_deduction' =>
                (int) $data['points_deduction'],

            'effective_from_journee' =>
                (int) $data['effective_from_journee'],

            'reason' =>
                $this->normalizeReason(
                    $data['reason'] ?? null
                ),
        ]);

        return redirect()
            ->route(
                'admin.seasons.clubs',
                $season
            )
            ->with(
                'success',
                'Pénalité TOP 14 ajoutée.'
            );
    }

    public function update(
        Request $request,
        Season $season,
        SeasonClubPenalty $penalty
    ): RedirectResponse {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $penalty = $season
            ->clubPenalties()
            ->whereKey($penalty->id)
            ->firstOrFail();

        $data = $request->validate([
            'points_deduction' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'effective_from_journee' => [
                'required',
                'integer',
                'min:1',
                'max:26',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (
            ! $this->clubBelongsToTop14(
                $season,
                (int) $penalty->club_id
            )
        ) {
            return redirect()
                ->route(
                    'admin.seasons.clubs',
                    $season
                )
                ->with(
                    'error',
                    'Cette pénalité ne concerne plus un club TOP 14 de la saison.'
                );
        }

        $penalty->update([
            'points_deduction' =>
                (int) $data['points_deduction'],

            'effective_from_journee' =>
                (int) $data['effective_from_journee'],

            'reason' =>
                $this->normalizeReason(
                    $data['reason'] ?? null
                ),
        ]);

        return redirect()
            ->route(
                'admin.seasons.clubs',
                $season
            )
            ->with(
                'success',
                'Pénalité TOP 14 modifiée.'
            );
    }

    public function destroy(
        Season $season,
        SeasonClubPenalty $penalty
    ): RedirectResponse {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $penalty = $season
            ->clubPenalties()
            ->whereKey($penalty->id)
            ->firstOrFail();

        $penalty->delete();

        return redirect()
            ->route(
                'admin.seasons.clubs',
                $season
            )
            ->with(
                'success',
                'Pénalité TOP 14 supprimée.'
            );
    }

    private function clubBelongsToTop14(
        Season $season,
        int $clubId
    ): bool {
        return $season
            ->clubs()
            ->where(
                'clubs.id',
                $clubId
            )
            ->wherePivot(
                'competition',
                'top14'
            )
            ->exists();
    }

    private function normalizeReason(
        ?string $reason
    ): ?string {
        if ($reason === null) {
            return null;
        }

        $reason = trim($reason);

        return $reason === ''
            ? null
            : $reason;
    }

    private function lockedRedirect(
        Season $season
    ): RedirectResponse {
        return redirect()
            ->route(
                'admin.seasons.clubs',
                $season
            )
            ->with(
                'error',
                'Cette saison est verrouillée : les pénalités ne peuvent plus être modifiées.'
            );
    }
}
