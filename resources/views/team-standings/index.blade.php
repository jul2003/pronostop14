@extends('layouts.pronos')

@section('content')

@php
    $journeeLabel = function ($journee) {
        if ($journee->type === 'regular') {
            return 'J'.$journee->number;
        }

        return $journee->name;
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Classement des équipes
        </div>

        <h2 class="fw-bold mb-1">
            {{ $selectedSeason->name }}
        </h2>

        <p class="text-muted mb-0">
            Classement calculé avec les journées régulières avant le {{ $currentDate->format('d/m/Y') }}
            ayant au moins un résultat saisi.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('results.season', $selectedSeason) }}"
           class="btn btn-outline-primary rounded-pill fw-bold px-4">
            Résultats
        </a>

        <a href="{{ route('rankings.general', $selectedSeason) }}"
           class="btn btn-warning rounded-pill fw-bold px-4">
            Classement joueurs
        </a>
    </div>
</div>

<div class="rugby-card p-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label for="seasonSelect" class="form-label fw-bold">
                Saison
            </label>

            <select id="seasonSelect" class="form-select">
                @foreach($seasons as $seasonOption)
                    <option value="{{ route('team-standings.season', $seasonOption) }}"
                            @selected($seasonOption->id === $selectedSeason->id)>
                        {{ $seasonOption->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-7">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                <span class="badge rounded-pill text-bg-dark">
                    {{ $clubs->count() }} club(s)
                </span>

                <span class="badge rounded-pill text-bg-primary">
                    {{ $includedJournees->count() }} journée(s)
                </span>

                <span class="badge rounded-pill text-bg-success">
                    {{ $playedMatchesCount }} match(s) joué(s)
                </span>
            </div>
        </div>
    </div>
</div>

@if($includedJournees->isEmpty())
    <div class="alert alert-info">
        Aucune journée régulière avant la date du jour ne possède encore de résultat saisi.
    </div>
@else
    <div class="rugby-card p-3 mb-4">
        <div class="fw-bold mb-2">
            Journées prises en compte
        </div>

        <div class="d-flex flex-wrap gap-2">
            @foreach($includedJournees as $journee)
                <span class="badge rounded-pill text-bg-light border">
                    {{ $journeeLabel($journee) }}
                </span>
            @endforeach
        </div>
    </div>
@endif

<div class="rugby-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 team-standing-table">
            <thead class="table-light">
                <tr>
                    <th class="text-center">Rang</th>
                    <th>Club</th>
                    <th class="text-center">J</th>
                    <th class="text-center">G</th>
                    <th class="text-center">N</th>
                    <th class="text-center">P</th>
                    <th class="text-center">Dom.</th>
                    <th class="text-center">Ext.</th>
                    <th class="text-center">BO</th>
                    <th class="text-center">BD</th>
                    <th class="text-center">Bonus</th>
                    <th class="text-center">Pts</th>
                </tr>
            </thead>

            <tbody>
                @forelse($standings as $row)
                    @php
                        $club = $row['club'];
                    @endphp

                    <tr>
                        <td class="text-center fw-bold">
                            {{ $row['rank'] }}
                        </td>

                        <td>
                            <div class="club-cell">
                                <img src="{{ $club->logo_url }}"
                                     alt="{{ $club->name }}"
                                     class="club-logo">

                                <div class="fw-bold">
                                    {{ $club->name }}
                                </div>
                            </div>
                        </td>

                        <td class="text-center">
                            {{ $row['played'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['won'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['drawn'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['lost'] }}
                        </td>

                        <td class="text-center text-muted">
                            {{ $row['home_played'] }}
                        </td>

                        <td class="text-center text-muted">
                            {{ $row['away_played'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['offensive_bonus'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['defensive_bonus'] }}
                        </td>

                        <td class="text-center">
                            {{ $row['bonus_total'] }}
                        </td>

                        <td class="text-center fw-bold fs-5">
                            {{ $row['points'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            Aucun club sur cette saison.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rugby-card p-4 mt-4">
    <div class="fw-bold mb-2">
        Barème utilisé
    </div>

    <div class="text-muted">
        Victoire : 4 pts · Nul : 2 pts · Défaite : 0 pt · Bonus offensif : +1 · Bonus défensif : +1.
    </div>

    <div class="text-muted small mt-2">
        Le classement est trié par points, victoires, nuls, bonus, puis défaites.
        L’application ne calcule pas de différence de points terrain car les scores exacts des matchs ne sont pas saisis.
    </div>
</div>

@endsection

@push('styles')
<style>
    .team-standing-table th,
    .team-standing-table td {
        white-space: nowrap;
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }

    .club-cell {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 260px;
    }

    .club-logo {
        width: 34px;
        height: 34px;
        object-fit: contain;
        flex: 0 0 auto;
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
