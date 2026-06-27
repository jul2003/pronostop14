@extends('layouts.pronos')

@section('content')

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
          action="{{ route('admin.seasons.journees.update', [$season, $journee]) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-6">
                <label class="form-label fw-bold">
                    Début de la journée
                </label>

                <div class="input-group">
                    <input type="date"
                           name="starts_at"
                           value="{{ old('starts_at', $journee->starts_at?->format('Y-m-d')) }}"
                           class="form-control app-date-input">

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
                <label class="form-label fw-bold">
                    Date limite des pronostics
                </label>

                <div class="input-group">
                    <input type="date"
                           name="prediction_deadline"
                           value="{{ old('prediction_deadline', $journee->prediction_deadline?->format('Y-m-d')) }}"
                           class="form-control app-date-input">

                    <button type="button"
                            class="btn btn-outline-secondary clear-date-button"
                            title="Effacer la date"
                            aria-label="Effacer la date">
                        ×
                    </button>
                </div>

                <div class="form-text">
                    Date limite de saisie des pronostics par les joueurs.
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
        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const group = button.closest('.input-group');

                if (! group) {
                    return;
                }

                const input = group.querySelector('.app-date-input');

                if (! input) {
                    return;
                }

                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
</script>

@endsection
