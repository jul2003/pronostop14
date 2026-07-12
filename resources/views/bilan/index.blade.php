@extends('layouts.pronos')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Bilan
        </div>

        <h2 class="fw-bold mb-1">
            Bilan — {{ $selectedSeason->name }}
        </h2>

        <p class="text-muted mb-0">
            Cette page est prête. Le contenu du bilan sera défini ensuite.
        </p>
    </div>

    <a href="{{ route('results.season', $selectedSeason) }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Résultats
    </a>
</div>

<div class="rugby-card p-4 mb-4">
    <label for="seasonSelect" class="form-label fw-bold">
        Saison
    </label>

    <select id="seasonSelect" class="form-select">
        @foreach($seasons as $seasonOption)
            <option value="{{ route('bilan.season', $seasonOption) }}"
                    @selected($seasonOption->id === $selectedSeason->id)>
                {{ $seasonOption->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="rugby-card p-4">
    <div class="alert alert-info mb-0">
        Donne-moi ensuite ce que tu veux afficher sur cette page bilan, et on la construira proprement.
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('seasonSelect');

        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                window.location.href = select.value;
            }
        });
    });
</script>
@endpush
