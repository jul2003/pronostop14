@extends('layouts.pronos')

@section('content')

@php
    $currentAppDateTime = $currentAppDateTime ?? app(\App\Services\AppDateService::class)->now();
@endphp

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
        Journées dont le premier match est passé et dont les résultats ne sont pas encore complets.
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
                    @if($season->is_locked)
                        Cette saison est verrouillée. Les résultats ne peuvent plus être modifiés.
                    @else
                        Cette page affiche les journées passées dont la saisie des résultats est incomplète.
                    @endif
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @if($season->is_locked)
                    <span class="badge text-bg-danger rounded-pill align-self-center">
                        Saison verrouillée
                    </span>
                @endif

                <a href="{{ route('admin.seasons.journees', $season) }}"
                   class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                    Voir toutes les journées
                </a>
            </div>
        </div>
    </div>

    @if($season->is_locked)
        <div class="alert alert-warning">
            <div class="fw-bold">
                Saison verrouillée
            </div>

            <div>
                Aucun résultat n’est proposé à la saisie, car la saison est figée.
                Pour corriger un résultat, il faut d’abord déverrouiller la saison depuis sa page d’édition.
            </div>
        </div>
    @elseif(! $preseasonNeedsResults && $journees->isEmpty())
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
                            <th class="text-center">Premier match</th>
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
                                    @if($preseasonJournee->first_match_at)
                                        {{ $preseasonJournee->first_match_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
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
                                $matchesCount = (int) $journee->matches_count;
                                $finishedMatchesCount = (int) $journee->finished_matches_count;

                                $displayExpectedCount = max(
                                    $expectedMatchesCount,
                                    $matchesCount
                                );

                                $matchesAreComplete = $matchesCount >= $expectedMatchesCount;

                                $resultsAreComplete = $finishedMatchesCount
                                    >= $displayExpectedCount;

                                $hasDelayedOpenMatch = $journee->matches
                                    ->filter(function ($match) use ($journee, $currentAppDateTime) {
                                        if ((bool) $match->is_finished) {
                                            return false;
                                        }

                                        if ($journee->predictions_enabled === false) {
                                            return false;
                                        }

                                        $deadline = $match
                                            ->predictionDeadlineException
                                            ?->prediction_deadline
                                            ?? $journee->first_match_at;

                                        if (! $deadline) {
                                            return false;
                                        }

                                        return $currentAppDateTime->lt($deadline);
                                    })
                                    ->isNotEmpty();
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $journee->name }}
                                    </div>

                                    @if($hasDelayedOpenMatch)
                                        <div class="small mt-1">
                                            <span class="badge rounded-pill text-bg-warning">
                                                Match décalé
                                            </span>
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
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($matchesAreComplete)
                                        <span class="badge bg-success">
                                            {{ $matchesCount }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            {{ $matchesCount }} / {{ $expectedMatchesCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($resultsAreComplete)
                                        <span class="badge bg-success">
                                            {{ $finishedMatchesCount }} / {{ $displayExpectedCount }}
                                        </span>
                                    @elseif($hasDelayedOpenMatch)
                                        <span class="badge bg-warning text-dark">
                                            {{ $finishedMatchesCount }} / {{ $displayExpectedCount }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            {{ $finishedMatchesCount }} / {{ $displayExpectedCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-primary">
                                        {{ $displayExpectedCount }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    @if(! $matchesAreComplete)
                                        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3">
                                            Saisir les matchs
                                        </a>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.results', [$season, $journee, 'from' => 'pending-results']) }}"
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
