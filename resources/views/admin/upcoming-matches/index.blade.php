@extends('layouts.pronos')

@section('content')

@php
    $currentAppDateTime = app(\App\Services\AppDateService::class)->now();
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
        Matchs à saisir
    </h2>

    <p class="text-muted mb-0">
        Prochaines journées à préparer pour la saison active. Tu peux créer les matchs, modifier la date du premier match et activer ou désactiver la saisie des pronostics.
    </p>
</div>

@if(! $season)
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucune saison active pour le moment.
        </div>
    </div>
@else
    <div class="rugby-card p-0 overflow-hidden">
        <div class="p-4 border-bottom">
            <h3 class="h5 fw-bold mb-1">
                {{ $season->name }}
            </h3>

            <p class="text-muted mb-0">
                Affichage des {{ $journeesToPrepareCount }} prochaine(s) journée(s) à préparer.
            </p>
        </div>

        @if($journees->isEmpty())
            <div class="p-4">
                <div class="alert alert-success mb-0">
                    Aucune journée à préparer pour le moment.
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Journée</th>
                            <th class="text-center">Premier match</th>
                            <th class="text-center">Matchs</th>
                            <th class="text-center">Saisie pronos</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journees as $journee)
                            @php
                                $expectedMatchesCount = $journee->expectedMatchesCount();
                                $matchesCount = (int) ($journee->matches_count ?? 0);

                                $journeeHasStarted = $journee->first_match_at !== null
                                    && $currentAppDateTime->greaterThanOrEqualTo($journee->first_match_at);

                                $preparationIsLocked = $season->is_locked || $journeeHasStarted;

                                $matchCountClass = '';

                                if ($expectedMatchesCount !== null && $matchesCount < (int) $expectedMatchesCount) {
                                    $matchCountClass = 'text-danger fw-bold';
                                }
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
                                        <span class="badge bg-danger">
                                            Manquant
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="{{ $matchCountClass }}">
                                        {{ $matchesCount }}

                                        @if($expectedMatchesCount !== null)
                                            / {{ $expectedMatchesCount }}
                                        @endif
                                    </span>
                                </td>

                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        @if($journee->predictions_enabled)
                                            <span class="badge rounded-pill text-bg-success">
                                                Activée
                                            </span>
                                        @else
                                            <span class="badge rounded-pill text-bg-secondary">
                                                Désactivée
                                            </span>
                                        @endif

                                        @if($season->is_locked)
                                            <span class="btn btn-sm btn-outline-secondary rounded-pill fw-bold disabled"
                                                  aria-disabled="true">
                                                Saison verrouillée
                                            </span>
                                        @else
                                            <form method="POST"
                                                  action="{{ route('admin.upcoming-matches.predictions.update', [$season, $journee]) }}">
                                                @csrf
                                                @method('PATCH')

                                                <input type="hidden"
                                                       name="predictions_enabled"
                                                       value="{{ $journee->predictions_enabled ? 0 : 1 }}">

                                                <button type="submit"
                                                        class="btn btn-sm rounded-pill fw-bold {{ $journee->predictions_enabled ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                    {{ $journee->predictions_enabled ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                        @if($preparationIsLocked)
                                            <span class="btn btn-sm btn-outline-primary rounded-pill fw-bold disabled"
                                                  aria-disabled="true">
                                                Matchs
                                            </span>
                                        @else
                                            <a href="{{ route('admin.seasons.journees.matches', [$season, $journee, 'from' => 'upcoming-matches']) }}"
                                               class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                                                Matchs
                                            </a>
                                        @endif

                                        @if($preparationIsLocked)
                                            <span class="btn btn-sm btn-outline-secondary rounded-pill fw-bold disabled"
                                                  aria-disabled="true">
                                                Date
                                            </span>
                                        @else
                                            <a href="{{ route('admin.seasons.journees.edit', [$season, $journee, 'from' => 'upcoming-matches']) }}"
                                               class="btn btn-sm rounded-pill fw-bold {{ $journee->first_match_at ? 'btn-outline-secondary' : 'btn-outline-danger' }}">
                                                Date
                                            </a>
                                        @endif
                                    </div>
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
