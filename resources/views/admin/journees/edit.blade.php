@extends('layouts.pronos')

@section('content')

@php
    $startsAtValue = old('starts_at', $journee->starts_at?->format('Y-m-d'));

    $predictionDeadlineValue = old(
        'prediction_deadline',
        $journee->prediction_deadline?->format('Y-m-d') ?? ''
    );
@endphp

<div class="mb-4">
    <a href="{{ route('admin.seasons.journees', $season) }}"
       class="text-decoration-none fw-bold">
        ← Retour aux journées
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        {{ $journee->name }}
    </h2>

    <p class="text-muted mb-0">
        Modifie la date sportive de la journée et la date limite de saisie des pronostics.
    </p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
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
                <label for="startsAtInput" class="form-label fw-bold">
                    Début de la journée
                </label>

                <div class="input-group">
                    <input type="date"
                           id="startsAtInput"
                           name="starts_at"
                           value="{{ $startsAtValue }}"
                           class="form-control app-date-input"
                           autocomplete="off">

                    <button type="button"
                            class="btn btn-outline-secondary clear-date-button"
                            title="Effacer la date"
                            aria-label="Effacer la date">
                        ×
                    </button>
                </div>

                <div class="form-text">
                    Date sportive de la journée. À partir de cette date, les matchs et la préparation ne sont plus modifiables.
                </div>
            </div>

            <div class="col-lg-6">
                <label for="predictionDeadlineInput" class="form-label fw-bold">
                    Date limite des pronostics
                </label>

                <div class="input-group">
                    <input type="date"
                           id="predictionDeadlineInput"
                           name="prediction_deadline"
                           value="{{ $predictionDeadlineValue }}"
                           class="form-control app-date-input"
                           autocomplete="off">

                    <button type="button"
                            id="updatePredictionDeadlineButton"
                            class="btn btn-outline-primary fw-bold"
                            title="Mettre la date limite à la veille du début de journée">
                        MAJ date limite
                    </button>

                    <button type="button"
                            class="btn btn-outline-secondary clear-date-button"
                            title="Effacer la date limite"
                            aria-label="Effacer la date limite">
                        ×
                    </button>
                </div>

                <div class="form-text">
                    Clique sur MAJ date limite pour mettre automatiquement la date limite à la veille du début de journée.
                    Laisse ce champ vide pour bloquer la saisie des pronostics de cette journée.
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startsAtInput = document.getElementById('startsAtInput');
        const predictionDeadlineInput = document.getElementById('predictionDeadlineInput');
        const updatePredictionDeadlineButton = document.getElementById('updatePredictionDeadlineButton');

        function dateMinusOneDay(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value + 'T00:00:00');

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            date.setDate(date.getDate() - 1);

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return year + '-' + month + '-' + day;
        }

        function updatePredictionDeadlineFromJourneeDate() {
            if (!startsAtInput || !predictionDeadlineInput) {
                return;
            }

            const automaticDeadline = dateMinusOneDay(startsAtInput.value);

            if (!automaticDeadline) {
                return;
            }

            predictionDeadlineInput.value = automaticDeadline;
        }

        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const group = button.closest('.input-group');

                if (!group) {
                    return;
                }

                const input = group.querySelector('.app-date-input');

                if (!input) {
                    return;
                }

                input.value = '';

                input.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
        });

        if (startsAtInput) {
            startsAtInput.addEventListener('change', function () {
                updatePredictionDeadlineFromJourneeDate();
            });
        }

        if (updatePredictionDeadlineButton) {
            updatePredictionDeadlineButton.addEventListener('click', function () {
                updatePredictionDeadlineFromJourneeDate();
            });
        }
    });
</script>

@endsection
