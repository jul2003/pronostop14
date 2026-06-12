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

            <input type="date"
                name="prediction_deadline"
                class="form-control"
                value="{{ $journee->prediction_deadline?->format('Y-m-d') }}">
        </div>

        <button class="btn btn-warning rounded-pill fw-bold">
            Enregistrer
        </button>

    </form>

</div>

@endsection
