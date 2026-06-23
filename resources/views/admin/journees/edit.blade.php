@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.seasons.journees', $season) }}">
        ← Retour aux journées
    </a>

    <h2 class="fw-bold mt-3">
        {{ $journee->name }}
    </h2>
</div>

<div class="rugby-card p-4">

    <form method="POST"
          action="{{ route('admin.seasons.journees.update', [$season, $journee]) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
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
        </div>

        <button class="btn btn-warning rounded-pill fw-bold">
            Enregistrer
        </button>

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
