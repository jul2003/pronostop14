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
        Résultats à saisir
    </h2>

    <p class="text-muted mb-0">
        Journées dont la date limite est dépassée et dont les résultats ne sont pas encore complets.
    </p>
</div>

@if(! $season)
    <div class="alert alert-warning">
        Aucune saison active n’est définie. Active une saison pour afficher les résultats à saisir.
    </div>
@else
    <div class="rugby-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="h5 fw-bold mb-1">
                    Saison active : {{ $season->name }}
                </h3>

                <p class="text-muted mb-0">
                    Cette page affiche les journées dont la deadline est passée et dont la saisie des résultats est incomplète.
                </p>
            </div>

            <a href="{{ route('admin.seasons.journees', $season) }}"
               class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                Voir toutes les journées
            </a>
        </div>
    </div>

    @if(! $preseasonNeedsResults && $journees->isEmpty())
        <div class="alert alert-success">
            Aucun résultat à saisir pour le moment.
        </div>
    @else
        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journée</th>
                            <th>Type</th>
                            <th class="text-center">Deadline</th>
                            <th class="text-center">Matchs saisis</th>
                            <th class="text-center">Résultats saisis</th>
                            <th class="text-center">Résultats attendus</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if($preseasonNeedsResults && $preseasonJournee)
                            <tr>
                                <td class="fw-bold">
                                    {{ $preseasonJournee->name }}
                                </td>

                                <td>
                                    Avant-saison
                                </td>

                                <td class="text-center">
                                    {{ $preseasonJournee->prediction_deadline?->format('d/m/Y H:i') }}
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        —
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if($preseasonResultsCount >= $preseasonQuestionsCount)
                                        <span class="badge bg-success">
                                            {{ $preseasonResultsCount }} / {{ $preseasonQuestionsCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            {{ $preseasonResultsCount }} / {{ $preseasonQuestionsCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        {{ $preseasonQuestionsCount }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('admin.seasons.preseason-results.edit', $season) }}"
                                       class="btn btn-sm btn-warning rounded-pill fw-bold px-3">
                                        Saisir les résultats avant-saison
                                    </a>
                                </td>
                            </tr>
                        @endif

                        @foreach($journees as $journee)
                            @php
                                $expectedMatchesCount = $journee->expectedMatchesCount();
                                $matchesAreComplete = (int) $journee->matches_count >= $expectedMatchesCount;
                                $resultsAreComplete = (int) $journee->finished_matches_count >= $expectedMatchesCount;
                            @endphp

                            <tr>
                                <td class="fw-bold">
                                    {{ $journee->name }}
                                </td>

                                <td>
                                    {{ $journee->type_label }}
                                </td>

                                <td class="text-center">
                                    {{ $journee->prediction_deadline?->format('d/m/Y H:i') }}
                                </td>

                                <td class="text-center">
                                    @if($matchesAreComplete)
                                        <span class="badge bg-success">
                                            {{ $journee->matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            {{ $journee->matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($resultsAreComplete)
                                        <span class="badge bg-success">
                                            {{ $journee->finished_matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            {{ $journee->finished_matches_count }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        {{ $expectedMatchesCount }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    @if(! $matchesAreComplete)
                                        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3">
                                            Saisir les matchs
                                        </a>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.results', [$season, $journee]) }}"
                                           class="btn btn-sm btn-warning rounded-pill fw-bold px-3">
                                            Saisir les résultats
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

@endsection
