@extends('layouts.pronos')

@section('content')

@php
    $usedClubIds = collect($usedClubIds)->map(fn ($id) => (int) $id)->toArray();

    $availableClubs = $clubs->reject(
        fn ($club) => in_array((int) $club->id, $usedClubIds, true)
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
        Clique sur les clubs dans l’ordre : domicile, extérieur, domicile, extérieur...
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

<div class="row g-4">

    <div class="col-lg-5">
        <div class="rugby-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 fw-bold mb-0">
                    Clubs disponibles
                </h3>

                <span class="badge text-bg-primary rounded-pill">
                    <span id="availableCount">{{ $availableClubs->count() }}</span>
                </span>
            </div>

            @if($availableClubs->count() < 2)

                <div class="alert alert-success mb-0">
                    Tous les clubs disponibles ont déjà été utilisés pour cette journée.
                </div>

            @else

                <div id="availableClubs" class="club-picker">
                    @foreach($availableClubs as $club)
                        <button type="button"
                                class="club-picker-item"
                                data-club-id="{{ $club->id }}"
                                data-club-name="{{ $club->name }}">

                            <div class="d-flex align-items-center gap-2">

                                <img src="{{ $club->logo_url }}"
                                    alt="{{ $club->name }}"
                                    class="club-logo">

                                <span>{{ $club->name }}</span>

                            </div>

                        </button>
                    @endforeach
                </div>

            @endif
        </div>
    </div>

    <div class="col-lg-7">
        <div class="rugby-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 fw-bold mb-0">
                    Matchs à créer
                </h3>

                <button type="button"
                        id="resetSelection"
                        class="btn btn-sm btn-outline-secondary rounded-pill">
                    Réinitialiser
                </button>
            </div>

            <form method="POST"
                  action="{{ route('admin.seasons.journees.matches.storeBulk', [$season, $journee]) }}"
                  id="bulkMatchesForm">
                @csrf

                <div id="selectedClubsInputs"></div>

                <div id="draftMatches" class="draft-matches">
                    <div class="alert alert-info mb-0">
                        Aucun club sélectionné pour le moment.
                    </div>
                </div>

                <button type="submit"
                        id="saveMatchesButton"
                        class="btn btn-warning rounded-pill fw-bold mt-4 px-4"
                        disabled>
                    Enregistrer les matchs
                </button>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 fw-bold mb-0">
                    Matchs enregistrés
                </h3>

                <span class="badge text-bg-primary rounded-pill">
                    {{ $matches->count() }}
                </span>
            </div>

            @if($matches->isEmpty())

                <div class="alert alert-info mb-0">
                    Aucun match enregistré pour cette journée.
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
    const selectedClubs = [];

    const availableClubs = document.getElementById('availableClubs');
    const draftMatches = document.getElementById('draftMatches');
    const selectedClubsInputs = document.getElementById('selectedClubsInputs');
    const saveMatchesButton = document.getElementById('saveMatchesButton');
    const resetSelection = document.getElementById('resetSelection');
    const availableCount = document.getElementById('availableCount');

    function renderDraftMatches() {

        selectedClubsInputs.innerHTML = '';

        selectedClubs.forEach(club => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'clubs[]';
            input.value = club.id;

            selectedClubsInputs.appendChild(input);
        });

        if (selectedClubs.length === 0) {
            draftMatches.innerHTML = `
                <div class="alert alert-info mb-0">
                    Aucun club sélectionné pour le moment.
                </div>
            `;
        } else {
            let html = '<div class="list-group">';

            for (let i = 0; i < selectedClubs.length; i += 2) {
                const home = selectedClubs[i];
                const away = selectedClubs[i + 1];

                html += `
                    <div class="list-group-item">
                        <div class="fw-bold mb-2">
                            Match ${Math.floor(i / 2) + 1}
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button"
                                    class="selected-club-chip"
                                    onclick="removeSelectedClub(${i})">
                                ${home.name}
                            </button>

                            ${away ? `
                                <button type="button"
                                        class="selected-club-chip"
                                        onclick="removeSelectedClub(${i + 1})">
                                    ${away.name}
                                </button>
                            ` : `
                                <span class="text-muted">
                                    En attente...
                                </span>
                            `}
                        </div>
                    </div>
                `;
            }

            html += '</div>';

            if (selectedClubs.length % 2 !== 0) {
                html += `
                    <div class="alert alert-warning mt-3 mb-0">
                        Sélectionne encore un club pour compléter le dernier match.
                    </div>
                `;
            }

            draftMatches.innerHTML = html;
        }

        saveMatchesButton.disabled =
            selectedClubs.length < 2 || selectedClubs.length % 2 !== 0;

        resetSelection.disabled = selectedClubs.length === 0;

        if (availableCount && availableClubs) {
            availableCount.textContent = availableClubs.querySelectorAll('.club-picker-item:not(.d-none)').length;
        }
    }

    function resetDraft() {
        selectedClubs.length = 0;

        document.querySelectorAll('.club-picker-item').forEach(button => {
            button.classList.remove('d-none');
        });

        renderDraftMatches();
    }

    function removeSelectedClub(index) {
        const removedClub = selectedClubs[index];

        selectedClubs.splice(index, 1);

        const button = document.querySelector(
            `.club-picker-item[data-club-id="${removedClub.id}"]`
        );

        if (button) {
            button.classList.remove('d-none');
        }

        renderDraftMatches();
    }

    document.querySelectorAll('.club-picker-item').forEach(button => {
        button.addEventListener('click', function () {
            selectedClubs.push({
                id: this.dataset.clubId,
                name: this.dataset.clubName,
            });

            this.classList.add('d-none');

            renderDraftMatches();
        });
    });

    resetSelection?.addEventListener('click', resetDraft);

    renderDraftMatches();

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
@endpush
