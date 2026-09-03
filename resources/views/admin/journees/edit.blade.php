@extends('layouts.pronos')

@section('content')

@php
    $defaultFirstMatchTime =
        $defaultFirstMatchTime ?? '12:00';

    $defaultPredictionsVisibleTime =
        $defaultPredictionsVisibleTime ?? '17:00';

    $predictionRecapEarlyVisibilityEnabled =
        $predictionRecapEarlyVisibilityEnabled ?? false;

    $suggestedFirstMatchAt =
        $suggestedFirstMatchAt ?? null;

    $suggestedFirstMatchSourceJournee =
        $suggestedFirstMatchSourceJournee ?? null;

    $fromUpcomingMatches =
        $fromUpcomingMatches
        ?? request('from') === 'upcoming-matches';

    $backUrl = $fromUpcomingMatches
        ? route('admin.upcoming-matches.index')
        : route(
            'admin.seasons.journees',
            $season
        );

    $backLabel = $fromUpcomingMatches
        ? 'Retour aux matchs à saisir'
        : 'Retour aux journées';

    $sourceFirstMatchAt =
        $journee->first_match_at
        ?: $suggestedFirstMatchAt;

    $firstMatchDateValue = old(
        'first_match_date',
        $sourceFirstMatchAt?->format('Y-m-d') ?? ''
    );

    $firstMatchTimeValue = old(
        'first_match_time',
        $sourceFirstMatchAt?->format('H:i') ?? ''
    );

    $predictionsVisibleDateValue = old(
        'predictions_visible_date',
        $journee->predictions_visible_at?->format('Y-m-d') ?? ''
    );

    $predictionsVisibleTimeValue = old(
        'predictions_visible_time',
        $journee->predictions_visible_at?->format('H:i') ?? ''
    );

    $predictionsEnabledValue = (bool) old(
        'predictions_enabled',
        $journee->predictions_enabled
    );

    $firstMatchSuggestionIsApplied =
        ! $journee->first_match_at
        && $suggestedFirstMatchAt;
@endphp


@include('admin.partials.back-link', [
    'href' => $backUrl,
    'label' => $backLabel,
])


<div class="mb-4">

    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Modifier {{ $journee->name }}
    </h2>

    <p class="text-muted mb-0">
        Configure la clôture de la saisie,
        l’affichage des pronostics et l’activation
        de la journée.
    </p>

</div>


@if($firstMatchSuggestionIsApplied)

    <div class="alert alert-info">

        <div class="fw-bold">
            Date proposée automatiquement
        </div>

        <div>
            La date du premier match était vide.
            Elle est préremplie avec

            <span class="fw-bold">
                {{ $suggestedFirstMatchAt->format('d/m/Y H:i') }}
            </span>

            à partir de

            <span class="fw-bold">
                {{ $suggestedFirstMatchSourceJournee?->name }}
            </span>

            + 7 jours.

            Clique sur Enregistrer pour l’appliquer.
        </div>

    </div>

@endif


<div class="rugby-card p-4">

    <form method="POST"
          action="{{ route('admin.seasons.journees.update', [$season, $journee]) }}"
          autocomplete="off">

        @csrf
        @method('PUT')


        @if($fromUpcomingMatches)

            <input type="hidden"
                   name="from"
                   value="upcoming-matches">

        @endif


        <div class="row g-4">


            {{-- DATE DU PREMIER MATCH --}}

            <div class="col-lg-6">

                <label for="firstMatchDateInput"
                       class="form-label fw-bold">
                    Date du premier match
                </label>

                <div class="input-group">

                    <input type="date"
                           id="firstMatchDateInput"
                           name="first_match_date"
                           value="{{ $firstMatchDateValue }}"
                           class="form-control @error('first_match_date') is-invalid @enderror"
                           autocomplete="off">

                    <button type="button"
                            class="btn btn-outline-secondary clear-date-button"
                            data-target="firstMatchDateInput"
                            data-time-target="firstMatchTimeInput"
                            title="Effacer la date et l’heure"
                            aria-label="Effacer la date et l’heure">
                        ×
                    </button>

                </div>

                <div class="form-text">
                    Cette date correspond à la clôture
                    normale de la saisie des pronostics.
                </div>

                @error('first_match_date')

                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- HEURE DU PREMIER MATCH --}}

            <div class="col-lg-6">

                <label for="firstMatchTimeInput"
                       class="form-label fw-bold">
                    Heure du premier match
                </label>

                <div class="input-group">

                    <input type="time"
                           id="firstMatchTimeInput"
                           name="first_match_time"
                           value="{{ $firstMatchTimeValue }}"
                           class="form-control @error('first_match_time') is-invalid @enderror"
                           autocomplete="off">

                    <button type="button"
                            id="applyDefaultFirstMatchTimeButton"
                            class="btn btn-outline-primary fw-bold"
                            data-default-time="{{ $defaultFirstMatchTime }}">
                        Appliquer heure par défaut
                    </button>

                </div>

                <div class="form-text">
                    Heure par défaut actuelle :
                    {{ $defaultFirstMatchTime }}.

                    Quand tu changes la date,
                    cette heure est automatiquement appliquée.
                </div>

                @error('first_match_time')

                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- AFFICHAGE DES PRONOSTICS --}}

            @if($journee->type !== 'preseason')

                <div class="col-12">

                    <div class="border rounded-3 p-3 bg-light">

                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">

                            <div class="fw-bold">
                                Affichage du récapitulatif des pronostics
                            </div>

                            @if($predictionRecapEarlyVisibilityEnabled)

                                <span class="badge rounded-pill text-bg-success">
                                    Affichage anticipé activé
                                </span>

                            @else

                                <span class="badge rounded-pill text-bg-secondary">
                                    Affichage anticipé désactivé
                                </span>

                            @endif

                        </div>


                        <p class="text-muted small mb-3">
                            Cette date peut être renseignée
                            indépendamment de l’activation globale
                            de la fonctionnalité.
                        </p>


                        <div class="row g-3">


                            {{-- DATE D'AFFICHAGE --}}

                            <div class="col-lg-6">

                                <label for="predictionsVisibleDateInput"
                                       class="form-label fw-bold">
                                    Date d’affichage
                                </label>

                                <div class="input-group">

                                    <input type="date"
                                           id="predictionsVisibleDateInput"
                                           name="predictions_visible_date"
                                           value="{{ $predictionsVisibleDateValue }}"
                                           class="form-control @error('predictions_visible_date') is-invalid @enderror"
                                           autocomplete="off">

                                    <button type="button"
                                            class="btn btn-outline-secondary clear-date-button"
                                            data-target="predictionsVisibleDateInput"
                                            data-time-target="predictionsVisibleTimeInput"
                                            title="Effacer la date et l’heure d’affichage"
                                            aria-label="Effacer la date et l’heure d’affichage">
                                        ×
                                    </button>

                                </div>

                                @error('predictions_visible_date')

                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>


                            {{-- HEURE D'AFFICHAGE --}}

                            <div class="col-lg-6">

                                <label for="predictionsVisibleTimeInput"
                                       class="form-label fw-bold">
                                    Heure d’affichage
                                </label>

                                <div class="input-group">

                                    <input type="time"
                                           id="predictionsVisibleTimeInput"
                                           name="predictions_visible_time"
                                           value="{{ $predictionsVisibleTimeValue }}"
                                           class="form-control @error('predictions_visible_time') is-invalid @enderror"
                                           autocomplete="off">

                                    <button type="button"
                                            id="applyDefaultPredictionsVisibleTimeButton"
                                            class="btn btn-outline-primary fw-bold"
                                            data-default-time="{{ $defaultPredictionsVisibleTime }}">
                                        Appliquer heure par défaut
                                    </button>

                                </div>

                                <div class="form-text">
                                    Heure par défaut actuelle :
                                    {{ $defaultPredictionsVisibleTime }}.

                                    Quand tu changes la date,
                                    cette heure est automatiquement appliquée.
                                </div>

                                @error('predictions_visible_time')

                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        @if($predictionRecapEarlyVisibilityEnabled)

                            <div class="alert alert-info mt-3 mb-0">

                                <div class="fw-bold">
                                    Fonctionnalité activée
                                </div>

                                <div class="small">
                                    Si une date d’affichage antérieure
                                    au premier match est renseignée,
                                    les pronostics des joueurs seront
                                    visibles sur la page Résultats
                                    dès cette date.
                                </div>

                            </div>

                        @else

                            <div class="alert alert-secondary mt-3 mb-0">

                                <div class="fw-bold">
                                    Fonctionnalité actuellement désactivée
                                </div>

                                <div class="small">
                                    Tu peux quand même préparer
                                    et enregistrer cette date.

                                    Tant que l’option globale reste
                                    désactivée, la page Résultats
                                    l’ignore et attend la date
                                    du premier match.
                                </div>

                            </div>

                        @endif


                        @if(
                            $journee->predictions_visible_at
                            && $journee->first_match_at
                            && $journee->predictions_visible_at->lt(
                                $journee->first_match_at
                            )
                        )

                            <div class="alert alert-light border mt-3 mb-0">

                                <div class="fw-bold mb-1">
                                    Configuration enregistrée
                                </div>

                                <div class="small">
                                    Affichage prévu le

                                    <strong>
                                        {{ $journee->predictions_visible_at->format('d/m/Y à H:i') }}
                                    </strong>

                                    et clôture de la saisie le

                                    <strong>
                                        {{ $journee->first_match_at->format('d/m/Y à H:i') }}
                                    </strong>.
                                </div>

                            </div>

                        @elseif(
                            $journee->predictions_visible_at
                            && $journee->first_match_at
                            && $journee->predictions_visible_at->gte(
                                $journee->first_match_at
                            )
                        )

                            <div class="alert alert-warning mt-3 mb-0">

                                <div class="fw-bold">
                                    Date non anticipée
                                </div>

                                <div class="small">
                                    La date d’affichage est égale
                                    ou postérieure au premier match.

                                    Dans ce cas, les pronostics deviennent
                                    visibles dès le premier match,
                                    comme avec le fonctionnement normal.
                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            @endif


            {{-- ACTIVATION DE LA SAISIE --}}

            <div class="col-12">

                <div class="border rounded-3 p-3 bg-light">

                    <input type="hidden"
                           name="predictions_enabled"
                           value="0">

                    <div class="form-check form-switch">

                        <input type="checkbox"
                               id="predictionsEnabledInput"
                               name="predictions_enabled"
                               value="1"
                               class="form-check-input"
                               @checked($predictionsEnabledValue)>

                        <label for="predictionsEnabledInput"
                               class="form-check-label fw-bold">
                            Activer la saisie des pronostics
                            pour cette journée
                        </label>

                    </div>

                    <div class="form-text">
                        Si cette case est décochée,
                        la journée ne sera pas proposée dans “Pronos”
                        et aucun pronostic ne pourra être enregistré,
                        même si la date du premier match
                        est dans le futur.
                    </div>

                </div>

            </div>

        </div>


        <div class="alert alert-info mt-4 mb-0">

            <div class="fw-bold">
                Règle de verrouillage
            </div>

            <div>
                Si le premier match est prévu le

                <span class="fw-bold">
                    05/09/2026 à 12:00
                </span>,

                les pronostics restent modifiables jusqu’à

                <span class="fw-bold">
                    05/09/2026 11:59:59
                </span>,

                uniquement si la saisie est activée.
            </div>

        </div>


        @if($journee->type !== 'preseason')

            <div class="alert alert-secondary mt-3 mb-0">

                <div class="fw-bold">
                    Affichage des pronostics
                </div>

                <div>
                    Par exemple, avec une date d’affichage
                    au

                    <strong>
                        04/09/2026 à 17:00
                    </strong>

                    et un premier match le

                    <strong>
                        05/09/2026 à 12:00
                    </strong>,

                    les pronostics pourront devenir visibles
                    dès le 04/09 à 17:00 alors que leur saisie
                    restera ouverte jusqu’au 05/09 à 12:00,
                    uniquement si la fonctionnalité globale
                    est activée.
                </div>

            </div>

        @endif


        <div class="d-flex justify-content-end mt-4">

            <button type="submit"
                    class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer
            </button>

        </div>

    </form>

</div>

@endsection


@push('scripts')

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const firstMatchDateInput =
                document.getElementById(
                    'firstMatchDateInput'
                );

            const firstMatchTimeInput =
                document.getElementById(
                    'firstMatchTimeInput'
                );

            const applyDefaultFirstMatchTimeButton =
                document.getElementById(
                    'applyDefaultFirstMatchTimeButton'
                );


            const predictionsVisibleDateInput =
                document.getElementById(
                    'predictionsVisibleDateInput'
                );

            const predictionsVisibleTimeInput =
                document.getElementById(
                    'predictionsVisibleTimeInput'
                );

            const applyDefaultPredictionsVisibleTimeButton =
                document.getElementById(
                    'applyDefaultPredictionsVisibleTimeButton'
                );


            function defaultFirstMatchTime() {

                return applyDefaultFirstMatchTimeButton
                    ?.dataset
                    .defaultTime
                    || '12:00';

            }


            function defaultPredictionsVisibleTime() {

                return applyDefaultPredictionsVisibleTimeButton
                    ?.dataset
                    .defaultTime
                    || '17:00';

            }


            if (
                firstMatchDateInput
                && firstMatchTimeInput
            ) {

                firstMatchDateInput.addEventListener(
                    'change',
                    function () {

                        if (
                            ! firstMatchDateInput.value
                        ) {
                            return;
                        }

                        firstMatchTimeInput.value =
                            defaultFirstMatchTime();

                    }
                );

            }


            if (
                applyDefaultFirstMatchTimeButton
                && firstMatchTimeInput
            ) {

                applyDefaultFirstMatchTimeButton.addEventListener(
                    'click',
                    function () {

                        firstMatchTimeInput.value =
                            defaultFirstMatchTime();

                    }
                );

            }


            if (
                predictionsVisibleDateInput
                && predictionsVisibleTimeInput
            ) {

                predictionsVisibleDateInput.addEventListener(
                    'change',
                    function () {

                        if (
                            ! predictionsVisibleDateInput.value
                        ) {
                            return;
                        }

                        predictionsVisibleTimeInput.value =
                            defaultPredictionsVisibleTime();

                    }
                );

            }


            if (
                applyDefaultPredictionsVisibleTimeButton
                && predictionsVisibleTimeInput
            ) {

                applyDefaultPredictionsVisibleTimeButton.addEventListener(
                    'click',
                    function () {

                        predictionsVisibleTimeInput.value =
                            defaultPredictionsVisibleTime();

                    }
                );

            }


            document
                .querySelectorAll(
                    '.clear-date-button'
                )
                .forEach(
                    function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const target =
                                    document.getElementById(
                                        button.dataset.target
                                    );

                                const timeTarget =
                                    document.getElementById(
                                        button.dataset.timeTarget
                                    );

                                if (target) {
                                    target.value = '';
                                }

                                if (timeTarget) {
                                    timeTarget.value = '';
                                }

                            }
                        );

                    }
                );

        }
    );
</script>

@endpush
