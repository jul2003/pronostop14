@extends('layouts.pronos')

@section('content')

@php
    $currentAppDateTime = app(\App\Services\AppDateService::class)->now();
@endphp

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.index'),
    'label' => 'Retour aux saisons',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Journées — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Les journées sont consultables uniquement.
        @else
            Gère les journées, les dates du premier match, les matchs, l’ouverture des pronostics et les résultats.
        @endif
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les journées, dates et matchs de cette saison ne peuvent plus être modifiés.
            Les résultats restent accessibles en consultation.
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if($journees->isEmpty())

    <div class="rugby-card p-4">
        <div class="alert alert-info">
            Aucune journée n’a encore été générée pour cette saison.
        </div>

        @if($season->is_locked)
            <span class="btn btn-warning rounded-pill fw-bold px-4 disabled"
                  aria-disabled="true">
                Générer les journées
            </span>
        @else
            <form method="POST"
                  action="{{ route('admin.seasons.generateJournees', $season) }}">
                @csrf

                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Générer les journées
                </button>
            </form>
        @endif
    </div>

@else
    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;">N°</th>
                        <th>Journée</th>
                        <th>Type</th>
                        <th class="text-center">Premier match</th>
                        <th class="text-center">Matchs / résultats</th>
                        <th class="text-center">Pronos</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($journees as $journee)
                        @php
                            $journeeHasStarted = $journee->first_match_at !== null
                                && $currentAppDateTime->greaterThanOrEqualTo($journee->first_match_at);

                            $preparationIsLocked = $season->is_locked || $journeeHasStarted;

                            $expectedMatchesCount = $journee->expectedMatchesCount();
                            $matchesCount = (int) ($journee->matches_count ?? 0);
                            $finishedMatchesCount = (int) ($journee->finished_matches_count ?? 0);

                            $progressBadgeClass = 'text-bg-secondary';

                            if ($expectedMatchesCount !== null) {
                                if ($matchesCount < (int) $expectedMatchesCount) {
                                    $progressBadgeClass = 'text-bg-danger';
                                } elseif ($finishedMatchesCount < $matchesCount) {
                                    $progressBadgeClass = 'text-bg-warning';
                                } else {
                                    $progressBadgeClass = 'text-bg-success';
                                }
                            }
                        @endphp

                        <tr>
                            <td class="fw-bold">
                                {{ $journee->number }}
                            </td>

                            <td>
                                <div class="fw-bold">
                                    {{ $journee->name }}
                                </div>

                                @if($season->is_locked)
                                    <div class="text-muted small mt-1">
                                        Saison verrouillée : consultation uniquement.
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $journee->type_label }}
                            </td>

                            <td class="text-center">
                                @if($journee->first_match_at)
                                    {{ $journee->first_match_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="badge bg-danger">
                                        Manquant
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($expectedMatchesCount === null)
                                    <span class="badge rounded-pill text-bg-secondary">
                                        —
                                    </span>
                                @else
                                    <span class="badge rounded-pill {{ $progressBadgeClass }}">
                                        {{ $matchesCount }}/{{ $finishedMatchesCount }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($journee->predictions_enabled)
                                    <span class="badge rounded-pill text-bg-success">
                                        Actifs
                                    </span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">
                                        Inactifs
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-inline-grid gap-2"
                                     style="grid-template-columns: 92px 82px 92px;">
                                    @if($preparationIsLocked)
                                        <span class="btn btn-sm btn-outline-secondary rounded-pill fw-bold disabled"
                                              aria-disabled="true">
                                            Modifier
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.edit', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                                            Modifier
                                        </a>
                                    @endif

                                    @if($journee->type === 'preseason')
                                        <span></span>

                                        <a href="{{ route('admin.seasons.preseason-results.edit', $season) }}"
                                           class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                            Résultats
                                        </a>
                                    @else
                                        @if($preparationIsLocked)
                                            <span class="btn btn-sm btn-outline-primary rounded-pill fw-bold disabled"
                                                  aria-disabled="true">
                                                Matchs
                                            </span>
                                        @else
                                            <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
                                               class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                                                Matchs
                                            </a>
                                        @endif

                                        <a href="{{ route('admin.seasons.journees.results', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                            Résultats
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
