@extends('layouts.pronos')

@section('content')

@php
    $defaultFirstMatchTime = $defaultFirstMatchTime ?? '12:00';

    $suggestedFirstMatchAt = $suggestedFirstMatchAt ?? null;
    $suggestedFirstMatchSourceJournee = $suggestedFirstMatchSourceJournee ?? null;

    $fromUpcomingMatches = $fromUpcomingMatches ?? request('from') === 'upcoming-matches';

    $backUrl = $fromUpcomingMatches
        ? route('admin.upcoming-matches.index')
        : route('admin.seasons.journees', $season);

    $backLabel = $fromUpcomingMatches
        ? 'Retour aux matchs à saisir'
        : 'Retour aux journées';

    $sourceFirstMatchAt = $journee->first_match_at ?: $suggestedFirstMatchAt;

    $firstMatchDateValue = old(
        'first_match_date',
        $sourceFirstMatchAt?->format('Y-m-d') ?? ''
    );

    $firstMatchTimeValue = old(
        'first_match_time',
        $sourceFirstMatchAt?->format('H:i') ?? ''
    );

    $predictionsEnabledValue = (bool) old(
        'predictions_enabled',
        $journee->predictions_enabled
    );

    $firstMatchSuggestionIsApplied = ! $journee->first_match_at && $suggestedFirstMatchAt;
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
        La saisie des pronostics reste ouverte uniquement si elle est activée et tant que la date et l’heure actuelles sont strictement inférieures à la date du premier match.
    </p>
</div>

@if($firstMatchSuggestionIsApplied)
    <div class="alert alert-info">
        <div class="fw-bold">
            Date proposée automatiquement
        </div>

        <div>
            La date du premier match était vide. Elle est préremplie avec
            <span class="fw-bold">{{ $suggestedFirstMatchAt->format('d/m/Y H:i') }}</span>
            à partir de
            <span class="fw-bold">{{ $suggestedFirstMatchSourceJournee?->name }}</span>
            + 7 jours. Clique sur Enregistrer pour l’appliquer.
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
            <div class="col-lg-6">
                <label for="firstMatchDateInput" class="form-label fw-bold">
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
                    Laisse la date vide pour supprimer la date du premier match.
                </div>

                @error('first_match_date')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="col-lg-6">
                <label for="firstMatchTimeInput" class="form-label fw-bold">
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
                    Heure par défaut actuelle : {{ $defaultFirstMatchTime }}.
                    Quand tu changes la date, cette heure est automatiquement appliquée.
                </div>

                @error('first_match_time')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

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

                        <label for="predictionsEnabledInput" class="form-check-label fw-bold">
                            Activer la saisie des pronostics pour cette journée
                        </label>
                    </div>

                    <div class="form-text">
                        Si cette case est décochée, la journée ne sera pas proposée dans “Pronos” et aucun pronostic ne pourra être enregistré, même si la date du premier match est dans le futur.
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
                <span class="fw-bold">06/09/2025 à 12:00</span>,
                les pronostics sont saisissables jusqu’à
                <span class="fw-bold">06/09/2025 11:59:59</span>,
                uniquement si la saisie est activée.
                À partir de 12:00, ils sont verrouillés.
            </div>
        </div>

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
    document.addEventListener('DOMContentLoaded', function () {
        const firstMatchDateInput = document.getElementById('firstMatchDateInput');
        const firstMatchTimeInput = document.getElementById('firstMatchTimeInput');
        const applyDefaultButton = document.getElementById('applyDefaultFirstMatchTimeButton');

        function defaultFirstMatchTime() {
            return applyDefaultButton?.dataset.defaultTime || '12:00';
        }

        function applyDefaultTimeOnDateChange() {
            if (!firstMatchDateInput || !firstMatchTimeInput) {
                return;
            }

            if (!firstMatchDateInput.value) {
                return;
            }

            firstMatchTimeInput.value = defaultFirstMatchTime();
        }

        if (firstMatchDateInput) {
            firstMatchDateInput.addEventListener('change', applyDefaultTimeOnDateChange);
        }

        if (applyDefaultButton && firstMatchTimeInput) {
            applyDefaultButton.addEventListener('click', function () {
                firstMatchTimeInput.value = defaultFirstMatchTime();
            });
        }

        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.target);
                const timeTarget = document.getElementById(button.dataset.timeTarget);

                if (target) {
                    target.value = '';
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (timeTarget) {
                    timeTarget.value = '';
                }
            });
        });
    });
</script>
@endpush
