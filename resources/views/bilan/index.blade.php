@extends('layouts.pronos')

@section('content')

@php
    $safeColor = function ($value, $fallback = '#6C757D') {
        $color = strtoupper((string) $value);

        return preg_match('/^#[0-9A-F]{6}$/', $color)
            ? $color
            : $fallback;
    };

    $lockedJournees = $journees
        ->filter(fn ($journee) => $journee->isLocked())
        ->values();

    $seasonPlayerIds = $players
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->toArray();

    $rankedRows = collect();
    $rank = 0;
    $position = 0;
    $previousPoints = null;

    foreach ($rankingRows as $row) {
        $position++;
        $totalPoints = (int) ($row['total_points'] ?? 0);

        if ($previousPoints !== $totalPoints) {
            $rank = $position;
        }

        $row['rank'] = $rank;
        $rankedRows->push($row);

        $previousPoints = $totalPoints;
    }

    $leader = $rankedRows->first();

    $playedMatchesCount = $lockedJournees->sum(function ($journee) {
        return $journee->matches
            ->filter(fn ($match) => ! blank($match->actual_result))
            ->count();
    });

    $journeeSummaries = collect();

    foreach ($lockedJournees as $journee) {
        $scores = $journee->userScores
            ->filter(fn ($score) => in_array((int) $score->user_id, $seasonPlayerIds, true))
            ->values();

        $bestScore = $scores->isEmpty()
            ? 0
            : (int) $scores->max('total_points');

        $winnerNames = $scores
            ->filter(fn ($score) => (int) $score->total_points === $bestScore)
            ->map(function ($score) use ($players) {
                $player = $players->firstWhere('id', (int) $score->user_id);

                return $player?->display_name ?? 'Joueur';
            })
            ->values();

        $perfectBonusCount = 0;

        foreach ($players as $player) {
            if ((int) ($journeePerfectBonuses[$journee->id][$player->id] ?? 0) > 0) {
                $perfectBonusCount++;
            }
        }

        $playedMatches = $journee->matches
            ->filter(fn ($match) => ! blank($match->actual_result))
            ->count();

        $journeeSummaries->push([
            'journee' => $journee,
            'winner_names' => $winnerNames,
            'best_score' => $bestScore,
            'average_score' => $scores->isEmpty() ? 0 : (float) $scores->avg('total_points'),
            'perfect_bonus_count' => $perfectBonusCount,
            'played_matches' => $playedMatches,
            'matches_count' => $journee->matches->count(),
        ]);
    }

    $playerStats = collect();

    foreach ($players as $player) {
        $rankingRow = $rankedRows->first(fn ($row) => $row['user']->id === $player->id);

        $journeeWins = 0;
        $bestJourneeScore = null;
        $perfectBonusCount = 0;
        $perfectBonusPoints = 0;
        $pronosticatedMatches = 0;
        $goodResults = 0;
        $goodTries = 0;
        $goodBonuses = 0;

        foreach ($lockedJournees as $journee) {
            $scores = $journee->userScores
                ->filter(fn ($score) => in_array((int) $score->user_id, $seasonPlayerIds, true))
                ->values();

            $score = $scores->firstWhere('user_id', $player->id);
            $scoreTotal = (int) ($score?->total_points ?? 0);

            if ($score) {
                $bestJourneeScore = $bestJourneeScore === null
                    ? $scoreTotal
                    : max($bestJourneeScore, $scoreTotal);

                $bestScoreForJournee = $scores->isEmpty()
                    ? 0
                    : (int) $scores->max('total_points');

                if ($scoreTotal === $bestScoreForJournee) {
                    $journeeWins++;
                }
            }

            $perfectBonus = (int) ($journeePerfectBonuses[$journee->id][$player->id] ?? 0);

            if ($perfectBonus > 0) {
                $perfectBonusCount++;
                $perfectBonusPoints += $perfectBonus;
            }

            foreach ($journee->matches as $match) {
                $breakdown = $matchBreakdowns[$match->id][$player->id] ?? null;

                if (! $breakdown || ($breakdown['result_status'] ?? 'neutral') === 'neutral') {
                    continue;
                }

                $pronosticatedMatches++;

                if (($breakdown['result_status'] ?? null) === 'good') {
                    $goodResults++;
                }

                if (($breakdown['tries_status'] ?? null) === 'good') {
                    $goodTries++;
                }

                if (($breakdown['home_bonus_status'] ?? null) === 'good') {
                    $goodBonuses++;
                }

                if (($breakdown['away_bonus_status'] ?? null) === 'good') {
                    $goodBonuses++;
                }
            }
        }

        $playerStats->push([
            'user' => $player,
            'rank' => $rankingRow['rank'] ?? null,
            'journee_points' => (int) ($rankingRow['journee_points'] ?? 0),
            'preseason_points' => (int) ($rankingRow['preseason_points'] ?? 0),
            'total_points' => (int) ($rankingRow['total_points'] ?? 0),
            'journee_wins' => $journeeWins,
            'best_journee_score' => $bestJourneeScore,
            'perfect_bonus_count' => $perfectBonusCount,
            'perfect_bonus_points' => $perfectBonusPoints,
            'pronosticated_matches' => $pronosticatedMatches,
            'good_results' => $goodResults,
            'good_tries' => $goodTries,
            'good_bonuses' => $goodBonuses,
        ]);
    }

    $playerStatsById = $playerStats->keyBy(fn ($row) => $row['user']->id);

    $preseasonRows = $players
        ->map(function ($player) use ($preseasonQuestionTotals, $preseasonBonusTotals, $preseasonTotals) {
            return [
                'user' => $player,
                'questions' => (int) ($preseasonQuestionTotals[$player->id] ?? 0),
                'bonus' => (int) ($preseasonBonusTotals[$player->id] ?? 0),
                'total' => (int) ($preseasonTotals[$player->id] ?? 0),
            ];
        })
        ->sortByDesc('total')
        ->values();
@endphp

<div id="top"></div>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Bilan
        </div>

        <h2 class="fw-bold mb-1">
            Bilan — {{ $selectedSeason->name }}
        </h2>

        <p class="text-muted mb-0">
            Synthèse de la saison, des journées, des bonus et des performances joueurs.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('results.season', $selectedSeason) }}"
           class="btn btn-warning rounded-pill fw-bold px-4">
            Résultats
        </a>
    </div>
</div>

<div class="rugby-card p-4 mb-4">
    <label for="seasonSelect" class="form-label fw-bold">
        Saison
    </label>

    <select id="seasonSelect" class="form-select">
        @foreach($seasons as $seasonOption)
            <option value="{{ route('bilan.season', $seasonOption) }}"
                    @selected($seasonOption->id === $selectedSeason->id)>
                {{ $seasonOption->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase">
                Leader
            </div>

            <div class="fs-4 fw-bold">
                {{ $leader ? $leader['user']->display_name : '—' }}
            </div>

            <div class="text-muted">
                {{ $leader ? (int) $leader['total_points'].' pts' : 'Aucun point' }}
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase">
                Joueurs
            </div>

            <div class="fs-4 fw-bold">
                {{ $players->count() }}
            </div>

            <div class="text-muted">
                inscrits sur la saison
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase">
                Journées clôturées
            </div>

            <div class="fs-4 fw-bold">
                {{ $lockedJournees->count() }}
            </div>

            <div class="text-muted">
                prises en compte
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase">
                Matchs joués
            </div>

            <div class="fs-4 fw-bold">
                {{ $playedMatchesCount }}
            </div>

            <div class="text-muted">
                avec résultat saisi
            </div>
        </div>
    </div>
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Classement bilan
        </h3>

        <p class="text-muted mb-0">
            Total des points journées et des points avant-saison visibles.
        </p>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 bilan-table">
            <thead class="table-light">
                <tr>
                    <th class="text-center">Rang</th>
                    <th>Joueur</th>
                    <th class="text-end">Points journées</th>
                    <th class="text-end">Avant-saison</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rankedRows as $row)
                    @php
                        $player = $row['user'];
                    @endphp

                    <tr>
                        <td class="text-center fw-bold">
                            {{ $row['rank'] }}
                        </td>

                        <td>
                            <span class="player-name">
                                <span class="player-dot"
                                      style="background: {{ $safeColor($player->color ?? null) }}"></span>

                                {{ $player->display_name }}
                            </span>
                        </td>

                        <td class="text-end">
                            {{ (int) $row['journee_points'] }}
                        </td>

                        <td class="text-end">
                            {{ (int) $row['preseason_points'] }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ (int) $row['total_points'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Aucun joueur sur cette saison.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Bilan détaillé joueurs
        </h3>

        <p class="text-muted mb-0">
            Statistiques calculées sur les journées clôturées.
        </p>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 bilan-table">
            <thead class="table-light">
                <tr>
                    <th class="text-center">Rang</th>
                    <th>Joueur</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Victoires journée</th>
                    <th class="text-end">Bonus journée</th>
                    <th class="text-end">Meilleur score</th>
                    <th class="text-end">Résultats justes</th>
                    <th class="text-end">Essais exacts</th>
                    <th class="text-end">Bonus justes</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rankedRows as $row)
                    @php
                        $player = $row['user'];
                        $stats = $playerStatsById->get($player->id);
                    @endphp

                    <tr>
                        <td class="text-center fw-bold">
                            {{ $row['rank'] }}
                        </td>

                        <td>
                            <span class="player-name">
                                <span class="player-dot"
                                      style="background: {{ $safeColor($player->color ?? null) }}"></span>

                                {{ $player->display_name }}
                            </span>
                        </td>

                        <td class="text-end fw-bold">
                            {{ $stats['total_points'] ?? 0 }}
                        </td>

                        <td class="text-end">
                            {{ $stats['journee_wins'] ?? 0 }}
                        </td>

                        <td class="text-end">
                            {{ $stats['perfect_bonus_count'] ?? 0 }}
                            @if(($stats['perfect_bonus_points'] ?? 0) > 0)
                                <span class="text-muted">
                                    (+{{ $stats['perfect_bonus_points'] }})
                                </span>
                            @endif
                        </td>

                        <td class="text-end">
                            {{ $stats['best_journee_score'] ?? '—' }}
                        </td>

                        <td class="text-end">
                            {{ $stats['good_results'] ?? 0 }}
                            <span class="text-muted">
                                / {{ $stats['pronosticated_matches'] ?? 0 }}
                            </span>
                        </td>

                        <td class="text-end">
                            {{ $stats['good_tries'] ?? 0 }}
                        </td>

                        <td class="text-end">
                            {{ $stats['good_bonuses'] ?? 0 }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Aucun bilan joueur disponible.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Bilan par journée
        </h3>

        <p class="text-muted mb-0">
            Résumé des journées dont la date limite est dépassée.
        </p>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 bilan-table">
            <thead class="table-light">
                <tr>
                    <th>Journée</th>
                    <th>Vainqueur journée</th>
                    <th class="text-end">Meilleur score</th>
                    <th class="text-end">Moyenne</th>
                    <th class="text-end">Bonus journée</th>
                    <th class="text-end">Matchs joués</th>
                </tr>
            </thead>

            <tbody>
                @forelse($journeeSummaries as $summary)
                    <tr>
                        <td class="fw-bold">
                            {{ $summary['journee']->name }}
                        </td>

                        <td>
                            {{ $summary['winner_names']->isNotEmpty() ? $summary['winner_names']->implode(', ') : '—' }}
                        </td>

                        <td class="text-end fw-bold">
                            {{ $summary['best_score'] }}
                        </td>

                        <td class="text-end">
                            {{ number_format($summary['average_score'], 1, ',', ' ') }}
                        </td>

                        <td class="text-end">
                            {{ $summary['perfect_bonus_count'] }}
                        </td>

                        <td class="text-end">
                            {{ $summary['played_matches'] }}
                            <span class="text-muted">
                                / {{ $summary['matches_count'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Aucune journée clôturée pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Bilan avant-saison
        </h3>

        <p class="text-muted mb-0">
            Points des pronostics avant-saison et des bonus avant-saison.
        </p>
    </div>

    @if(! $preseasonIsVisible)
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Le bilan avant-saison sera visible après la date limite avant-saison.
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bilan-table">
                <thead class="table-light">
                    <tr>
                        <th>Joueur</th>
                        <th class="text-end">Questions</th>
                        <th class="text-end">Bonus</th>
                        <th class="text-end">Total avant-saison</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($preseasonRows as $row)
                        @php
                            $player = $row['user'];
                        @endphp

                        <tr>
                            <td>
                                <span class="player-name">
                                    <span class="player-dot"
                                          style="background: {{ $safeColor($player->color ?? null) }}"></span>

                                    {{ $player->display_name }}
                                </span>
                            </td>

                            <td class="text-end">
                                {{ $row['questions'] }}
                            </td>

                            <td class="text-end">
                                {{ $row['bonus'] }}
                            </td>

                            <td class="text-end fw-bold">
                                {{ $row['total'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Aucun point avant-saison.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="text-center my-4">
    <a href="#top"
       class="btn btn-outline-secondary rounded-pill fw-bold px-4">
        ↑ Retour en haut
    </a>
</div>

@endsection

@push('styles')
<style>
    .bilan-table th,
    .bilan-table td {
        padding-top: 0.55rem;
        padding-bottom: 0.55rem;
        white-space: nowrap;
    }

    .player-name {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 700;
    }

    .player-dot {
        width: 0.9rem;
        height: 0.9rem;
        border-radius: 999px;
        display: inline-block;
        border: 1px solid rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('seasonSelect');

        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                window.location.href = select.value;
            }
        });
    });
</script>
@endpush
