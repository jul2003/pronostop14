@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Matchs à saisir
    </h2>

    <p class="text-muted mb-0">
        Prépare les prochaines journées en saisissant les matchs et les dates limites nécessaires.
    </p>
</div>

@if(! $season)
    <div class="alert alert-warning">
        Aucune saison active n’est définie. Active une saison pour afficher les matchs à préparer.
    </div>
@else
    <div class="rugby-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="h5 fw-bold mb-1">
                    Saison active : {{ $season->name }}
                </h3>

                <p class="text-muted mb-0">
                    Cette page affiche les {{ $journeesToPrepareCount }} prochaine(s) journée(s)
                    à préparer, en tenant compte des matchs et des dates limites.
                </p>
            </div>

            <a href="{{ route('admin.app-settings.index') }}"
               class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                Modifier le nombre
            </a>
        </div>
    </div>

    @if($journees->isEmpty())
        <div class="alert alert-success">
            Toutes les prochaines journées sont préparées.
        </div>

        <div class="d-flex justify-content-end">
            <a href="{{ route('admin.seasons.journees', $season) }}"
               class="btn btn-outline-primary rounded-pill fw-bold px-4">
                Voir les journées
            </a>
        </div>
    @else
        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journée</th>
                            <th>Type</th>
                            <th class="text-center">Matchs saisis</th>
                            <th class="text-center">Matchs attendus</th>
                            <th class="text-center">Date limite</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journees as $journee)
                            @php
                                $expectedMatchesCount = $journee->expectedMatchesCount();
                                $matchesAreIncomplete = (int) $journee->matches_count < $expectedMatchesCount;
                                $deadlineIsMissing = $journee->prediction_deadline === null;
                            @endphp

                            <tr>
                                <td class="fw-bold">
                                    {{ $journee->name }}
                                </td>

                                <td>
                                    {{ $journee->type_label }}
                                </td>

                                <td class="text-center">
                                    @if($matchesAreIncomplete)
                                        <span class="badge bg-warning text-dark">
                                            {{ $journee->matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            {{ $journee->matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        {{ $expectedMatchesCount }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if($deadlineIsMissing)
                                        <span class="badge bg-danger">
                                            Manquante
                                        </span>
                                    @else
                                        {{ $journee->prediction_deadline->format('d/m/Y') }}
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if($matchesAreIncomplete)
                                        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}?from=upcoming-matches"
                                           class="btn btn-sm btn-warning rounded-pill fw-bold px-3">
                                            Saisir les matchs
                                        </a>
                                    @elseif($deadlineIsMissing)
                                        <a href="{{ route('admin.seasons.journees.edit', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-warning rounded-pill fw-bold px-3">
                                            Modifier la journée
                                        </a>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.edit', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3">
                                            Voir la journée
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <a href="{{ route('admin.seasons.journees', $season) }}"
               class="btn btn-outline-secondary rounded-pill fw-bold px-4">
                Voir toutes les journées
            </a>
        </div>
    @endif
@endif

@endsection
