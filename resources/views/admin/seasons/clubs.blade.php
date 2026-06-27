@extends('layouts.pronos')

@section('content')

@php
    $top14Count = count($selectedTop14);
    $prod2Count = count($selectedProd2);

    $top14BadgeClass = $top14Count === $season->top14_clubs_count
        ? 'badge text-bg-success rounded-pill'
        : 'badge text-bg-warning rounded-pill';

    $prod2BadgeClass = $prod2Count === $season->prod2_clubs_count
        ? 'badge text-bg-success rounded-pill'
        : 'badge text-bg-warning rounded-pill';
@endphp

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.edit', $season),
    'label' => 'Retour à la saison',
])

<div class="mb-4">
    <a href="{{ route('admin.seasons.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux saisons
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Clubs participants — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Les clubs participants sont consultables uniquement.
        @else
            Clique sur un club disponible pour l’affecter au TOP 14 ou à la PRO D2.
        @endif
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les clubs participants de cette saison ne peuvent plus être modifiés.
            Pour corriger la liste des clubs, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div id="clubsWarning" class="alert alert-warning d-none mb-4"></div>

<form id="season-clubs-form"
      method="POST"
      action="{{ route('admin.seasons.clubs.sync', $season) }}">
    @csrf

    <div id="hiddenInputs"></div>

    <div class="row g-4 align-items-start">
        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        Clubs disponibles
                    </h3>

                    @unless($season->is_locked)
                        <button type="submit"
                                name="redirect_after_save"
                                value="{{ route('admin.clubs.create', ['redirect_to' => request()->fullUrl()]) }}"
                                class="btn btn-sm btn-warning rounded-pill">
                            + Club
                        </button>
                    @endunless
                </div>

                <div id="availableClubs" class="club-picker">
                    @foreach($clubs as $club)
                        @if(!in_array($club->id, $selectedTop14) && !in_array($club->id, $selectedProd2))
                            <button type="button"
                                    class="club-picker-item"
                                    data-club-id="{{ $club->id }}"
                                    data-club-name="{{ $club->name }}"
                                    data-club-logo="{{ $club->logo_url }}"
                                    @disabled($season->is_locked)>
                                <img src="{{ $club->logo_url }}"
                                     alt="{{ $club->name }}"
                                     class="club-logo">

                                <span>{{ $club->name }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        TOP 14
                    </h3>

                    <span id="top14Badge" class="{{ $top14BadgeClass }}">
                        <span id="top14Count">{{ $top14Count }}</span>
                        / {{ $season->top14_clubs_count }}
                    </span>
                </div>

                @unless($season->is_locked)
                    <div class="d-grid gap-2 mb-3">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                onclick="moveSelectedClub('top14')">
                            Ajouter au TOP 14 →
                        </button>
                    </div>
                @endunless

                <div id="top14Clubs" class="season-club-list">
                    @foreach($clubs as $club)
                        @if(in_array($club->id, $selectedTop14))
                            <button type="button"
                                    class="season-club-item w-100 text-start"
                                    data-club-id="{{ $club->id }}"
                                    data-club-name="{{ $club->name }}"
                                    data-club-logo="{{ $club->logo_url }}"
                                    @unless($season->is_locked)
                                        onclick="removeClubFromCompetition(this, 'top14')"
                                    @endunless
                                    @disabled($season->is_locked)>
                                <img src="{{ $club->logo_url }}"
                                     alt="{{ $club->name }}"
                                     class="club-logo-small me-2">

                                {{ $club->name }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        PRO D2
                    </h3>

                    <span id="prod2Badge" class="{{ $prod2BadgeClass }}">
                        <span id="prod2Count">{{ $prod2Count }}</span>
                        / {{ $season->prod2_clubs_count }}
                    </span>
                </div>

                @unless($season->is_locked)
                    <div class="d-grid gap-2 mb-3">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                onclick="moveSelectedClub('prod2')">
                            Ajouter à la PRO D2 →
                        </button>
                    </div>
                @endunless

                <div id="prod2Clubs" class="season-club-list">
                    @foreach($clubs as $club)
                        @if(in_array($club->id, $selectedProd2))
                            <button type="button"
                                    class="season-club-item w-100 text-start"
                                    data-club-id="{{ $club->id }}"
                                    data-club-name="{{ $club->name }}"
                                    data-club-logo="{{ $club->logo_url }}"
                                    @unless($season->is_locked)
                                        onclick="removeClubFromCompetition(this, 'prod2')"
                                    @endunless
                                    @disabled($season->is_locked)>
                                <img src="{{ $club->logo_url }}"
                                     alt="{{ $club->name }}"
                                     class="club-logo-small me-2">

                                {{ $club->name }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @unless($season->is_locked)
        <div class="mt-4">
            <button class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer les clubs participants
            </button>
        </div>
    @endunless
</form>

@unless($season->is_locked)
    <script>
        const limits = {
            top14: {{ $season->top14_clubs_count }},
            prod2: {{ $season->prod2_clubs_count }},
        };

        let selectedAvailableClubs = [];

        function appendClubContent(button, club, logoClass) {
            const image = document.createElement('img');
            image.src = club.logo || '';
            image.alt = club.name || '';
            image.className = logoClass;

            const label = document.createElement('span');
            label.textContent = club.name || '';

            button.appendChild(image);
            button.appendChild(label);
        }

        function selectAvailableClub(button) {
            const clubId = button.dataset.clubId;

            if (button.classList.contains('is-selected')) {
                button.classList.remove('is-selected');

                selectedAvailableClubs = selectedAvailableClubs.filter(
                    club => club.id !== clubId
                );

                return;
            }

            button.classList.add('is-selected');

            selectedAvailableClubs.push({
                id: button.dataset.clubId,
                name: button.dataset.clubName,
                logo: button.dataset.clubLogo,
                element: button,
            });
        }

        document.querySelectorAll('#availableClubs .club-picker-item').forEach(button => {
            button.addEventListener('click', function () {
                selectAvailableClub(this);
            });
        });

        function moveSelectedClub(competition) {
            if (selectedAvailableClubs.length === 0) {
                showWarning('Sélectionne au moins un club disponible.');
                return;
            }

            const target = document.getElementById(competition + 'Clubs');
            const currentCount = target.querySelectorAll('.season-club-item').length;
            const availableSlots = limits[competition] - currentCount;

            if (selectedAvailableClubs.length > availableSlots) {
                showWarning(`Impossible d’ajouter ${selectedAvailableClubs.length} club(s). Il reste seulement ${availableSlots} place(s).`);
                return;
            }

            hideWarning();

            selectedAvailableClubs.forEach(club => {
                const chip = createSelectedChip(club, competition);
                target.appendChild(chip);
                club.element.remove();
            });

            selectedAvailableClubs = [];

            sortClubList(target);
            updateCounters();
            updateHiddenInputs();
        }

        function createSelectedChip(club, competition) {
            const chip = document.createElement('button');

            chip.type = 'button';
            chip.className = 'season-club-item w-100 text-start';
            chip.dataset.clubId = club.id;
            chip.dataset.clubName = club.name;
            chip.dataset.clubLogo = club.logo;
            chip.onclick = function () {
                removeClubFromCompetition(chip, competition);
            };

            appendClubContent(chip, club, 'club-logo-small me-2');

            return chip;
        }

        function removeClubFromCompetition(button, competition) {
            const club = {
                id: button.dataset.clubId,
                name: button.dataset.clubName,
                logo: button.dataset.clubLogo,
            };

            const availableButton = document.createElement('button');

            availableButton.type = 'button';
            availableButton.className = 'club-picker-item';
            availableButton.dataset.clubId = club.id;
            availableButton.dataset.clubName = club.name;
            availableButton.dataset.clubLogo = club.logo;

            appendClubContent(availableButton, club, 'club-logo');

            availableButton.addEventListener('click', function () {
                selectAvailableClub(this);
            });

            document.getElementById('availableClubs').appendChild(availableButton);

            button.remove();

            selectedAvailableClubs = selectedAvailableClubs.filter(
                selectedClub => selectedClub.id !== club.id
            );

            sortClubList(document.getElementById('availableClubs'));
            updateCounters();
            updateHiddenInputs();
        }

        function sortClubList(container) {
            [...container.children]
                .sort((a, b) => a.dataset.clubName.localeCompare(b.dataset.clubName, 'fr'))
                .forEach(child => container.appendChild(child));
        }

        function updateCounters() {
            const top14Count = document.querySelectorAll('#top14Clubs .season-club-item').length;
            const prod2Count = document.querySelectorAll('#prod2Clubs .season-club-item').length;

            document.getElementById('top14Count').textContent = top14Count;
            document.getElementById('prod2Count').textContent = prod2Count;

            document.getElementById('top14Badge').className =
                top14Count === limits.top14
                    ? 'badge text-bg-success rounded-pill'
                    : 'badge text-bg-warning rounded-pill';

            document.getElementById('prod2Badge').className =
                prod2Count === limits.prod2
                    ? 'badge text-bg-success rounded-pill'
                    : 'badge text-bg-warning rounded-pill';
        }

        function updateHiddenInputs() {
            const hiddenInputs = document.getElementById('hiddenInputs');

            hiddenInputs.innerHTML = '';

            document.querySelectorAll('#top14Clubs .season-club-item').forEach(button => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'top14_clubs[]';
                input.value = button.dataset.clubId;
                hiddenInputs.appendChild(input);
            });

            document.querySelectorAll('#prod2Clubs .season-club-item').forEach(button => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'prod2_clubs[]';
                input.value = button.dataset.clubId;
                hiddenInputs.appendChild(input);
            });
        }

        function showWarning(message) {
            const warning = document.getElementById('clubsWarning');

            warning.textContent = message;
            warning.classList.remove('d-none');

            window.scrollTo({
                top: warning.offsetTop - 100,
                behavior: 'smooth'
            });
        }

        function hideWarning() {
            document.getElementById('clubsWarning').classList.add('d-none');
        }

        sortClubList(document.getElementById('availableClubs'));
        sortClubList(document.getElementById('top14Clubs'));
        sortClubList(document.getElementById('prod2Clubs'));

        updateCounters();
        updateHiddenInputs();
    </script>
@endunless

@endsection
