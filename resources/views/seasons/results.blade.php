@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Résultats
    </div>

    <h2 class="fw-bold mb-1">
        Saison {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Calendrier, résultats et points des joueurs par journée.
    </p>
</div>

@foreach($journees as $journee)

    <div class="rugby-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">
                    {{ $journee->name }}
                </h3>

                <div class="text-muted small">
                    {{ $journee->type_label }}

                    @if($journee->prediction_deadline)
                        · limite : {{ $journee->prediction_deadline->format('d/m/Y') }}
                    @else
                        · limite non définie
                    @endif
                </div>
            </div>

            @if($journee->isLocked())
                <span class="badge text-bg-success rounded-pill">
                    Résultats visibles
                </span>
            @else
                <span class="badge text-bg-warning rounded-pill">
                    Pronostics ouverts
                </span>
            @endif
        </div>

        @if($journee->matches->isEmpty())

            <div class="alert alert-info mb-0">
                Aucun match renseigné pour cette journée.
            </div>

        @elseif(! $journee->isLocked())

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journee->matches->sortBy('position') as $match)
                            <tr>
                                <td class="fw-bold">
                                    <img src="{{ $match->homeClub->logo_url }}"
                                         alt="{{ $match->homeClub->name }}"
                                         class="club-logo-small me-2">

                                    {{ $match->homeClub->name }}

                                    <span class="text-muted mx-2">—</span>

                                    <img src="{{ $match->awayClub->logo_url }}"
                                         alt="{{ $match->awayClub->name }}"
                                         class="club-logo-small me-2">

                                    {{ $match->awayClub->name }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 result-grid">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center">Résultat</th>

                            @foreach($players as $player)
                                <th colspan="5" class="text-center">
                                    {{ $player->nickname ?? $player->name }}
                                </th>
                            @endforeach
                        </tr>

                        <tr>
                            <th></th>
                            <th></th>

                            @foreach($players as $player)
                                <th class="text-center">R</th>
                                <th class="text-center">E</th>
                                <th class="text-center">BD</th>
                                <th class="text-center">BE</th>
                                <th class="text-center">Pts</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journee->matches->sortBy('position') as $match)
                            <tr>
                                <td class="fw-bold match-cell">

                                    <div class="d-flex align-items-center gap-1">

                                        <img src="{{ $match->homeClub->logo_url }}"
                                            class="club-logo-small">

                                        <span>{{ $match->homeClub->short_name ?? $match->homeClub->name }}</span>

                                        <span class="mx-1 text-muted">-</span>

                                        <img src="{{ $match->awayClub->logo_url }}"
                                            class="club-logo-small">

                                        <span>{{ $match->awayClub->short_name ?? $match->awayClub->name }}</span>

                                    </div>

                                </td>

                                <td class="text-center text-nowrap fw-bold">
                                    {{ $match->actual_result ?? '-' }}
                                    /
                                    {{ $match->actual_tries ?? '-' }}
                                    /
                                    {{ $match->actual_home_bonus ?: '-' }}
                                    /
                                    {{ $match->actual_away_bonus ?: '-' }}
                                </td>

                                @foreach($players as $player)
                                    @php
                                        $prono = $match->pronos->firstWhere('user_id', $player->id);

                                        $resultClass = '';
                                        $triesClass = '';
                                        $homeBonusClass = '';
                                        $awayBonusClass = '';

                                        if ($prono && $match->actual_result) {
                                            if ($prono->predicted_result === $match->actual_result) {
                                                $resultClass = 'cell-ok-dark';

                                                if ($match->actual_tries !== null && $prono->predicted_tries !== null) {
                                                    $diff = abs($prono->predicted_tries - $match->actual_tries);

                                                    if ($diff === 0) {
                                                        $triesClass = 'cell-ok-dark';
                                                    } elseif ($diff === 1) {
                                                        $triesClass = 'cell-ok-light';
                                                    } else {
                                                        $triesClass = 'cell-ko';
                                                    }
                                                }

                                                if ($prono->predicted_home_bonus && $prono->predicted_home_bonus !== '-') {
                                                    $homeBonusClass = $prono->predicted_home_bonus === $match->actual_home_bonus
                                                        ? 'cell-ok-dark'
                                                        : 'cell-ko';
                                                }

                                                if ($prono->predicted_away_bonus && $prono->predicted_away_bonus !== '-') {
                                                    $awayBonusClass = $prono->predicted_away_bonus === $match->actual_away_bonus
                                                        ? 'cell-ok-dark'
                                                        : 'cell-ko';
                                                }
                                            } else {
                                                $resultClass = 'cell-ko';

                                                // Résultat faux : le reste n'est pas évalué.
                                                $triesClass = '';
                                                $homeBonusClass = '';
                                                $awayBonusClass = '';
                                            }
                                        }
                                    @endphp

                                    <td class="text-center {{ $resultClass }}">
                                        {{ $prono?->predicted_result ?? '-' }}
                                    </td>

                                    <td class="text-center {{ $triesClass }}">
                                        {{ $prono?->predicted_tries ?? '-' }}
                                    </td>

                                    <td class="text-center {{ $homeBonusClass }}">
                                        {{ $prono?->predicted_home_bonus ?: '-' }}
                                    </td>

                                    <td class="text-center {{ $awayBonusClass }}">
                                        {{ $prono?->predicted_away_bonus ?: '-' }}
                                    </td>

                                    <td class="text-center fw-bold">
                                        {{ $prono?->points ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">
                                Total journée
                            </td>

                            @foreach($players as $player)
                                @php
                                    $total = $journee->matches->sum(function ($match) use ($player) {
                                        return $match->pronos
                                            ->firstWhere('user_id', $player->id)
                                            ?->points ?? 0;
                                    });
                                @endphp

                                <td colspan="4"></td>
                                <td class="text-center">
                                    {{ $total }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

        @endif

    </div>

@endforeach

@endsection
