@extends('layouts.pronos')

@section('content')

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
        Sélectionne un ou plusieurs clubs, puis affecte-les au TOP 14 ou à la PRO D2.
    </p>
</div>

<div id="clubsWarning" class="alert alert-warning d-none mb-4"></div>

<form id="season-clubs-form"
      method="POST"
      action="{{ route('admin.seasons.clubs.sync', $season) }}">
    @csrf

    <div class="row g-4 align-items-start">

        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        Clubs disponibles
                    </h3>

                    <button type="submit"
                            name="redirect_after_save"
                            value="{{ route('admin.clubs.create', ['redirect_to' => request()->fullUrl()]) }}"
                            class="btn btn-sm btn-warning rounded-pill">
                        + Club
                    </button>
                </div>

                <select id="availableClubs"
                        class="form-select club-list"
                        multiple>
                    @foreach($clubs as $club)
                        @if(!in_array($club->id, $selectedTop14) && !in_array($club->id, $selectedProd2))
                            <option value="{{ $club->id }}">
                                {{ $club->name }} — {{ $club->short_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        TOP 14
                    </h3>

                    <span id="top14Badge" class="badge rounded-pill">
                        <span id="top14Count">{{ count($selectedTop14) }}</span>
                        / {{ $season->top14_clubs_count }}
                    </span>

                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                onclick="moveSelected('availableClubs', 'top14Clubs')">
                            →
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary rounded-pill"
                                onclick="moveSelected('top14Clubs', 'availableClubs')">
                            ←
                        </button>
                    </div>
                </div>

                <select id="top14Clubs"
                        name="top14_clubs[]"
                        class="form-select club-list"
                        multiple>
                    @foreach($clubs as $club)
                        @if(in_array($club->id, $selectedTop14))
                            <option value="{{ $club->id }}">
                                {{ $club->name }} — {{ $club->short_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rugby-card p-4">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h5 fw-bold mb-0">
                        PRO D2
                    </h3>

                    <span id="prod2Badge" class="badge rounded-pill">
                        <span id="prod2Count">{{ count($selectedProd2) }}</span>
                        / {{ $season->prod2_clubs_count }}
                    </span>

                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                onclick="moveSelected('availableClubs', 'prod2Clubs')">
                            →
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary rounded-pill"
                                onclick="moveSelected('prod2Clubs', 'availableClubs')">
                            ←
                        </button>
                    </div>
                </div>

                <select id="prod2Clubs"
                        name="prod2_clubs[]"
                        class="form-select club-list"
                        multiple>
                    @foreach($clubs as $club)
                        @if(in_array($club->id, $selectedProd2))
                            <option value="{{ $club->id }}">
                                {{ $club->name }} — {{ $club->short_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

    </div>

    <div class="mt-4">
        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Enregistrer les clubs participants
        </button>
    </div>
</form>

<script>
    const limits = {
        top14Clubs: {{ $season->top14_clubs_count }},
        prod2Clubs: {{ $season->prod2_clubs_count }},
    };

    function moveSelected(fromId, toId) {
        const from = document.getElementById(fromId);
        const to = document.getElementById(toId);

        const selectedOptions = [...from.selectedOptions];

        if (selectedOptions.length === 0) {
            showWarning('Sélectionne au moins un club à déplacer.');
            return;
        }

        if (limits[toId] !== undefined) {
            const availableSlots = limits[toId] - to.options.length;

            if (availableSlots <= 0) {
                showWarning(`Impossible : la limite est déjà atteinte (${limits[toId]} clubs).`);
                return;
            }

            if (selectedOptions.length > availableSlots) {
                showWarning(`Impossible d'ajouter ${selectedOptions.length} club(s). Il reste seulement ${availableSlots} place(s).`);
                return;
            }
        }

        hideWarning();

        selectedOptions.forEach(option => {
            option.selected = false;
            to.appendChild(option);
        });

        sortSelect(from);
        sortSelect(to);
        updateCounters();
    }

    function sortSelect(select) {
        [...select.options]
            .sort((a, b) => a.text.localeCompare(b.text, 'fr'))
            .forEach(option => select.appendChild(option));
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

    function updateCounters() {
        const top14Count = document.getElementById('top14Clubs').options.length;
        const prod2Count = document.getElementById('prod2Clubs').options.length;

        document.getElementById('top14Count').textContent = top14Count;
        document.getElementById('prod2Count').textContent = prod2Count;

        const top14Badge = document.getElementById('top14Badge');
        const prod2Badge = document.getElementById('prod2Badge');

        top14Badge.className =
            top14Count === limits.top14Clubs
                ? 'badge text-bg-success rounded-pill'
                : 'badge text-bg-warning rounded-pill';

        prod2Badge.className =
            prod2Count === limits.prod2Clubs
                ? 'badge text-bg-success rounded-pill'
                : 'badge text-bg-warning rounded-pill';
    }

    document.getElementById('season-clubs-form').addEventListener('submit', function () {
        document.querySelectorAll('#top14Clubs option, #prod2Clubs option')
            .forEach(option => {
                option.selected = true;
            });
    });

    ['availableClubs', 'top14Clubs', 'prod2Clubs'].forEach(id => {
        const select = document.getElementById(id);

        if (select) {
            sortSelect(select);
        }
    });

    updateCounters();
</script>

@endsection
