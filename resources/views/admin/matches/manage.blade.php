@extends('layouts.pronos')

@section('content')

@php
    $availableClubs = $clubs->reject(
        fn ($club) => in_array($club->id, $usedClubIds)
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
        Matchs — {{ $journee->name }}
    </h2>

    <p class="text-muted mb-0">
        Gestion des matchs de la journée.
    </p>
</div>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="rugby-card p-4">
            <h3 class="h5 fw-bold mb-3">
                Ajouter un match
            </h3>

            @if($availableClubs->count() >= 2)

                <form method="POST"
                      action="{{ route('admin.seasons.journees.matches.store', [$season, $journee]) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Club domicile
                        </label>

                        <select id="homeClubSelect"
                            name="home_club_id"
                            class="form-select"
                            required>
                            <option value="">
                                Sélectionner...
                            </option>

                            @foreach($availableClubs as $club)
                                <option value="{{ $club->id }}">
                                    {{ $club->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Club extérieur
                        </label>

                        <select id="awayClubSelect"
                            name="away_club_id"
                            class="form-select"
                            required>
                            <option value="">
                                Sélectionner...
                            </option>

                            @foreach($availableClubs as $club)
                                <option value="{{ $club->id }}">
                                    {{ $club->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-warning rounded-pill fw-bold w-100">
                        Ajouter le match
                    </button>
                </form>

            @else

                <div class="alert alert-success mb-0">
                    Tous les clubs disponibles ont déjà été utilisés pour cette journée.
                </div>

            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <div class="rugby-card p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 fw-bold mb-0">
                    Matchs
                </h3>

                <span class="badge text-bg-primary rounded-pill">
                    {{ $matches->count() }}
                </span>
            </div>

            @if($matches->isEmpty())

                <div class="alert alert-info mb-0">
                    Aucun match pour cette journée.
                </div>

            @else

                <ul id="matchesList" class="list-group">
                    @foreach($matches as $match)

                        <li class="list-group-item d-flex justify-content-between align-items-center"
                            data-id="{{ $match->id }}">

                            <div class="d-flex align-items-center gap-3">
                                <span class="drag-handle text-muted"
                                    style="cursor: grab;">
                                    ☰
                                </span>

                                <div class="fw-bold">
                                    {{ $match->homeClub->name }}
                                    <span class="text-muted mx-1">—</span>
                                    {{ $match->awayClub->name }}
                                </div>
                            </div>

                            <form method="POST"
                                  action="{{ route('admin.matches.destroy', $match) }}">
                                @csrf
                                @method('DELETE')

                                <button class="btn btn-sm btn-outline-danger rounded-pill">
                                    Supprimer
                                </button>
                            </form>

                        </li>

                    @endforeach
                </ul>

            @endif

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    window.addEventListener('load', function () {
        const list = document.getElementById('matchesList');

        if (!list || !window.Sortable) {
            return;
        }

        new window.Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-50',

            onEnd: function () {
                const matches = [...list.querySelectorAll('li')]
                    .map(item => item.dataset.id);

                fetch("{{ route('admin.seasons.journees.matches.reorder', [$season, $journee]) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ matches }),
                });
            },
        });
    });
</script>

<script>
    function syncClubSelects() {
        const homeSelect = document.getElementById('homeClubSelect');
        const awaySelect = document.getElementById('awayClubSelect');

        if (!homeSelect || !awaySelect) {
            return;
        }

        const homeValue = homeSelect.value;
        const awayValue = awaySelect.value;

        [...awaySelect.options].forEach(option => {
            option.hidden = option.value !== '' && option.value === homeValue;
        });

        [...homeSelect.options].forEach(option => {
            option.hidden = option.value !== '' && option.value === awayValue;
        });
    }

    document.getElementById('homeClubSelect')?.addEventListener('change', syncClubSelects);
    document.getElementById('awayClubSelect')?.addEventListener('change', syncClubSelects);

    syncClubSelects();
</script>
@endpush
