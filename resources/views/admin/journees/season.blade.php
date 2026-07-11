@extends('layouts.pronos')

@section('content')

@php
    $currentAppDate = app(\App\Services\AppDateService::class)
        ->now()
        ->copy()
        ->startOfDay();
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
            Gère les journées, les dates limites, les matchs et les résultats de cette saison.
            À partir de la date de début d’une journée, seuls les résultats restent accessibles.
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

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
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
                        <th class="text-center">Début</th>
                        <th class="text-center">Date limite</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($journees as $journee)
                        @php
                            $journeeDate = $journee->starts_at
                                ? $journee->starts_at->copy()->startOfDay()
                                : null;

                            $journeeIsLockedByDate = $journeeDate !== null
                                && $currentAppDate->greaterThanOrEqualTo($journeeDate);

                            $preparationIsLocked = $season->is_locked || $journeeIsLockedByDate;
                        @endphp

                        <tr>
                            <td class="fw-bold">
                                {{ $journee->number }}
                            </td>

                            <td>
                                <div class="fw-bold">
                                    {{ $journee->name }}
                                </div>

                                <div class="text-muted small">
                                    {{ $journee->slug }}
                                </div>

                                @if($season->is_locked)
                                    <div class="text-muted small mt-1">
                                        Saison verrouillée : consultation uniquement.
                                    </div>
                                @elseif($journeeIsLockedByDate)
                                    <div class="text-muted small mt-1">
                                        Journée commencée : seuls les résultats restent accessibles.
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $journee->type_label }}
                            </td>

                            <td class="text-center">
                                @if($journee->starts_at)
                                    {{ $journee->starts_at->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">
                                        Non défini
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($journee->prediction_deadline)
                                    {{ $journee->prediction_deadline->format('d/m/Y') }}
                                @else
                                    <span class="badge bg-danger">
                                        Manquante
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
