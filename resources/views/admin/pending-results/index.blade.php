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
        Journées dont le premier match a commencé et dont les résultats ne sont pas complets.
    </p>
</div>

@if(! $season)
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucune saison active pour le moment.
        </div>
    </div>
@elseif($season->is_locked)
    <div class="rugby-card p-4">
        <div class="alert alert-warning mb-0">
            La saison active est verrouillée.
        </div>
    </div>
@else
    @if($preseasonNeedsResults && $preseasonJournee)
        <div class="rugby-card p-0 overflow-hidden mb-4">
            <div class="p-4 border-bottom">
                <h3 class="h5 fw-bold mb-1">
                    Avant-saison
                </h3>

                <p class="text-muted mb-0">
                    Résultats avant-saison à compléter.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journée</th>
                            <th class="text-center">Premier match</th>
                            <th class="text-center">Questions</th>
                            <th class="text-center">Résultats saisis</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="fw-bold">
                                {{ $preseasonJournee->name }}
                            </td>

                            <td class="text-center">
                                @if($preseasonJournee->first_match_at)
                                    {{ $preseasonJournee->first_match_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">
                                        Non défini
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $preseasonQuestionsCount }}
                            </td>

                            <td class="text-center">
                                {{ $preseasonResultsCount }} / {{ $preseasonQuestionsCount }}
                            </td>

                            <td class="text-end">
                                <a href="{{ route('admin.seasons.preseason-results.edit', $season) }}"
                                   class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                    Résultats
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="rugby-card p-0 overflow-hidden">
        <div class="p-4 border-bottom">
            <h3 class="h5 fw-bold mb-1">
                Journées
            </h3>

            <p class="text-muted mb-0">
                Saison active : {{ $season->name }}
            </p>
        </div>

        @if($journees->isEmpty())
            <div class="p-4">
                <div class="alert alert-success mb-0">
                    Aucun résultat de journée à saisir pour le moment.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journée</th>
                            <th class="text-center">Premier match</th>
                            <th class="text-center">Matchs attendus</th>
                            <th class="text-center">Résultats saisis</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journees as $journee)
                            @php
                                $expectedMatchesCount = $journee->expectedMatchesCount();
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $journee->name }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ $journee->type_label }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($journee->first_match_at)
                                        {{ $journee->first_match_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">
                                            Non défini
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ $expectedMatchesCount ?? '—' }}
                                </td>

                                <td class="text-center">
                                    {{ $journee->finished_matches_count }} / {{ $journee->matches_count }}
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('admin.seasons.journees.results', [$season, $journee]) }}"
                                       class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                        Résultats
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

@endsection
