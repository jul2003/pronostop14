<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Services\AppDateService;
use Carbon\Carbon;

class TeamStandingController extends Controller
{
    public function index()
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with('error', 'Aucune saison active pour le moment.');
        }

        return redirect()->route('team-standings.season', $season);
    }

    public function season(Season $season, AppDateService $dateService)
    {
        $currentDate = $dateService->now();

        $seasons = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $clubs = $season->clubs()
            ->wherePivot('competition', 'top14')
            ->orderBy('name')
            ->get();

        $journees = $season->journees()
            ->where('type', 'regular')
            ->with([
                'matches.homeClub',
                'matches.awayClub',
            ])
            ->orderBy('number')
            ->orderBy('id')
            ->get();

        $includedJournees = $journees
            ->filter(function ($journee) use ($currentDate) {
                $journeeDate = $this->journeeDate($journee);

                if (! $journeeDate || ! $journeeDate->lt($currentDate)) {
                    return false;
                }

                return $journee->matches
                    ->contains(fn ($match) => filled($match->actual_result));
            })
            ->values();

        $standingsByClubId = [];

        foreach ($clubs as $club) {
            $standingsByClubId[$club->id] = [
                'club' => $club,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'home_played' => 0,
                'away_played' => 0,
                'offensive_bonus' => 0,
                'defensive_bonus' => 0,
                'bonus_total' => 0,
                'points' => 0,
                'rank' => null,
            ];
        }

        foreach ($includedJournees as $journee) {
            foreach ($journee->matches as $match) {
                if (blank($match->actual_result)) {
                    continue;
                }

                if (! isset($standingsByClubId[$match->home_club_id], $standingsByClubId[$match->away_club_id])) {
                    continue;
                }

                $this->applyMatchToStandings($standingsByClubId, $match);
            }
        }

        $standings = collect($standingsByClubId)
            ->sort(function ($a, $b) {
                return [
                    $b['points'],
                    $b['won'],
                    $b['drawn'],
                    $b['bonus_total'],
                    $a['lost'],
                    $a['club']->name,
                ] <=> [
                    $a['points'],
                    $a['won'],
                    $a['drawn'],
                    $a['bonus_total'],
                    $b['lost'],
                    $b['club']->name,
                ];
            })
            ->values();

        $rank = 0;
        $position = 0;
        $previousKey = null;

        $standings = $standings->map(function ($row) use (&$rank, &$position, &$previousKey) {
            $position++;

            $currentKey = [
                $row['points'],
                $row['won'],
                $row['drawn'],
                $row['bonus_total'],
                -$row['lost'],
            ];

            if ($previousKey !== $currentKey) {
                $rank = $position;
            }

            $row['rank'] = $rank;
            $previousKey = $currentKey;

            return $row;
        });

        $playedMatchesCount = $includedJournees->sum(function ($journee) {
            return $journee->matches
                ->filter(fn ($match) => filled($match->actual_result))
                ->count();
        });

        return view('team-standings.index', [
            'seasons' => $seasons,
            'selectedSeason' => $season,
            'currentDate' => $currentDate,
            'clubs' => $clubs,
            'includedJournees' => $includedJournees,
            'standings' => $standings,
            'playedMatchesCount' => $playedMatchesCount,
        ]);
    }

    private function applyMatchToStandings(array &$standingsByClubId, $match): void
    {
        $homeClubId = (int) $match->home_club_id;
        $awayClubId = (int) $match->away_club_id;

        $standingsByClubId[$homeClubId]['played']++;
        $standingsByClubId[$homeClubId]['home_played']++;

        $standingsByClubId[$awayClubId]['played']++;
        $standingsByClubId[$awayClubId]['away_played']++;

        $result = strtolower((string) $match->actual_result);

        if ($result === 'v') {
            $standingsByClubId[$homeClubId]['won']++;
            $standingsByClubId[$homeClubId]['points'] += 4;

            $standingsByClubId[$awayClubId]['lost']++;
        } elseif ($result === 'd') {
            $standingsByClubId[$awayClubId]['won']++;
            $standingsByClubId[$awayClubId]['points'] += 4;

            $standingsByClubId[$homeClubId]['lost']++;
        } elseif ($result === 'n') {
            $standingsByClubId[$homeClubId]['drawn']++;
            $standingsByClubId[$homeClubId]['points'] += 2;

            $standingsByClubId[$awayClubId]['drawn']++;
            $standingsByClubId[$awayClubId]['points'] += 2;
        }

        $this->applyBonusToClub($standingsByClubId[$homeClubId], $match->actual_home_bonus);
        $this->applyBonusToClub($standingsByClubId[$awayClubId], $match->actual_away_bonus);
    }

    private function applyBonusToClub(array &$clubStanding, ?string $bonus): void
    {
        $bonus = $this->normalizeBonus($bonus);

        if ($bonus === null) {
            return;
        }

        if ($bonus === 'o') {
            $clubStanding['offensive_bonus']++;
            $clubStanding['bonus_total']++;
            $clubStanding['points']++;

            return;
        }

        if ($bonus === 'd') {
            $clubStanding['defensive_bonus']++;
            $clubStanding['bonus_total']++;
            $clubStanding['points']++;
        }
    }

    private function normalizeBonus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['o', 'd'], true) ? $value : null;
    }

    private function journeeDate($journee): ?Carbon
    {
        if (! $journee->first_match_at) {
            return null;
        }

        return $journee->first_match_at instanceof Carbon
            ? $journee->first_match_at->copy()
            : Carbon::parse($journee->first_match_at);
    }
}
