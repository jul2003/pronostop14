@extends('layouts.pronos')

@section('content')

@php
    $defaultFirstMatchTime = $defaultFirstMatchTime ?? '12:00';

    $suggestedFirstMatchAt = $suggestedFirstMatchAt ?? null;
    $suggestedFirstMatchSourceJournee = $suggestedFirstMatchSourceJournee ?? null;

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
    'href' => route('admin.seasons.journees', $season),
    'label' => 'Retour aux journées',
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

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

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
                            title="Effacer la date"
                            aria-label="Effacer la date">
                        ×
                    </button>
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

        function applyDefaultTimeIfNeeded() {
            if (!firstMatchDateInput || !firstMatchTimeInput || !applyDefaultButton) {
                return;
            }

            if (!firstMatchDateInput.value) {
                return;
            }

            if (firstMatchTimeInput.value) {
                return;
            }

            firstMatchTimeInput.value = applyDefaultButton.dataset.defaultTime || '12:00';
        }

        if (firstMatchDateInput) {
            firstMatchDateInput.addEventListener('change', applyDefaultTimeIfNeeded);
        }

        if (applyDefaultButton && firstMatchTimeInput) {
            applyDefaultButton.addEventListener('click', function () {
                firstMatchTimeInput.value = applyDefaultButton.dataset.defaultTime || '12:00';
            });
        }

        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.target);

                if (!target) {
                    return;
                }

                target.value = '';
                target.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
</script>
@endpush
