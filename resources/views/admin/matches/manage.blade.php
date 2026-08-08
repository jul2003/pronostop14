@extends('layouts.pronos')

@section('content')

@php
    $usedClubIds = collect($usedClubIds)->map(fn ($id) => (int) $id)->toArray();

    $availableClubs = $clubs->reject(
        fn ($club) => in_array((int) $club->id, $usedClubIds, true)
    );

    $fromUpcomingMatches = request('from') === 'upcoming-matches';

    $backUrl = $fromUpcomingMatches
        ? route('admin.upcoming-matches.index')
        : route('admin.seasons.journees', $season);

    $backLabel = $fromUpcomingMatches
        ? 'Retour aux matchs à saisir'
        : 'Retour aux journées';

    $matchesRouteParameters = $fromUpcomingMatches
        ? [$season, $journee, 'from' => 'upcoming-matches']
        : [$season, $journee];

    $destroyRouteParameters = fn ($match) => $fromUpcomingMatches
        ? [$match, 'from' => 'upcoming-matches']
        : [$match];

    $preparationIsLocked = $season->is_locked || $journee->isLocked();

    $automaticSetup = $automaticSetup ?? [
        'title' => null,
        'message' => null,
        'pairs' => [],
    ];

    $matchesByPosition = $matches->keyBy(
        fn ($match) => (int) $match->position
    );

    $automaticPairs = collect($automaticSetup['pairs'] ?? [])
        ->values()
        ->map(function ($pair, $index) use ($matchesByPosition) {
            $position = (int) ($pair['position'] ?? ($index + 1));
            $homeClub = $pair['home'] ?? null;
            $awayClub = $pair['away'] ?? null;

            $pairIsComplete = array_key_exists('is_complete', $pair)
                ? (bool) $pair['is_complete']
                : ($homeClub !== null && $awayClub !== null);

            $pairIsComplete = $pairIsComplete
                && $homeClub !== null
                && $awayClub !== null;

            $existingMatch = $matchesByPosition->get($position);

            $alreadyCreated = $existingMatch
                && $pairIsComplete
                && (int) $existingMatch->home_club_id === (int) $homeClub->id
                && (int) $existingMatch->away_club_id === (int) $awayClub->id;

            return array_merge($pair, [
                'position' => $position,
                'home' => $homeClub,
                'away' => $awayClub,
                'is_complete' => $pairIsComplete,
                'existing_match' => $existingMatch,
                'already_created' => $alreadyCreated,
                'position_is_occupied' => $existingMatch && ! $alreadyCreated,
            ]);
        })
        ->sortBy('position')
        ->values();

    $automaticReadyPairs = $automaticPairs
        ->filter(
            fn ($pair) => $pair['is_complete']
                && ! $pair['existing_match']
        )
        ->values();

    $automaticKnownTeamsCount = $automaticPairs->sum(function ($pair) {
        return (empty($pair['home']) ? 0 : 1)
            + (empty($pair['away']) ? 0 : 1);
    });

    $automaticOccupiedPairsCount = $automaticPairs
        ->where('position_is_occupied', true)
        ->count();

    $automaticCreatedPairsCount = $automaticPairs
        ->where('already_created', true)
        ->count();
@endphp

<div class="mb-4">
    <a href="{{ $backUrl }}"
       class="text-decoration-none fw-bold">
        ← {{ $backLabel }}
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Matchs — {{ $journee->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Les matchs sont consultables uniquement.
        @elseif($journee->isLocked())
            Cette journée est verrouillée. Les matchs sont consultables uniquement.
        @else
            Clique sur les clubs dans l’ordre : domicile, extérieur, domicile, extérieur...
        @endif
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les matchs de cette saison ne peuvent plus être modifiés.
            Pour les corriger, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@elseif($journee->isLocked())
    <div class="alert alert-warning">
        <div class="fw-bold">
            Journée verrouillée
        </div>

        <div>
            Cette journée est verrouillée : les matchs ne peuvent plus être ajoutés, supprimés ou réordonnés.
        </div>
    </div>
@endif

@if(! empty($automaticSetup['title']) || ! empty($automaticSetup['message']) || $automaticPairs->isNotEmpty())
    <div class="rugby-card p-4 mb-4 border border-primary-subtle">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">
                    {{ $automaticSetup['title'] ?: 'Alimentation automatique' }}
                </h3>

                @if(! empty($automaticSetup['message']))
                    <p class="text-muted mb-0">
                        {{ $automaticSetup['message'] }}
                    </p>
                @endif
            </div>

            <span class="badge rounded-pill text-bg-primary px-3 py-2">
                Automatique
            </span>
        </div>

        @if($automaticPairs->isEmpty())
            <div class="alert alert-info mb-0">
                Les conditions ne sont pas encore réunies pour alimenter automatiquement cette page.
            </div>
        @else
            <div class="row g-3 mb-3">
                @foreach($automaticPairs as $pair)
                    @php
                        $homeClub = $pair['home'] ?? null;
                        $awayClub = $pair['away'] ?? null;
                        $pairIsComplete = (bool) $pair['is_complete'];
                        $existingMatch = $pair['existing_match'];
                        $alreadyCreated = (bool) $pair['already_created'];
                        $positionIsOccupied = (bool) $pair['position_is_occupied'];

                        $homeLabel = $pair['home_label'] ?? 'Équipe 1';
                        $awayLabel = $pair['away_label'] ?? 'Équipe 2';

                        $homeSource = $pair['home_source'] ?? null;
                        $awaySource = $pair['away_source'] ?? null;

                        $homePlaceholder = $pair['home_placeholder']
                            ?? 'Équipe à déterminer';

                        $awayPlaceholder = $pair['away_placeholder']
                            ?? 'Équipe à déterminer';

                        $statusClass = match (true) {
                            $alreadyCreated => 'text-bg-secondary',
                            $positionIsOccupied => 'text-bg-danger',
                            $pairIsComplete => 'text-bg-success',
                            default => 'text-bg-warning',
                        };

                        $statusLabel = match (true) {
                            $alreadyCreated => 'Déjà créé',
                            $positionIsOccupied => 'Position occupée',
                            $pairIsComplete => 'Prêt à créer',
                            default => 'En attente',
                        };
                    @endphp

                    <div class="col-12 {{ $automaticPairs->count() > 1 ? 'col-xl-6' : '' }}">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold">
                                        {{ $pair['label'] }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $pair['description'] }}
                                    </div>
                                </div>

                                <span class="badge rounded-pill {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="automatic-match-grid mt-3">
                                <div class="automatic-team-slot {{ $homeClub ? '' : 'automatic-team-slot-pending' }}">
                                    <div class="text-uppercase text-muted fw-bold small mb-2">
                                        {{ $homeLabel }}
                                    </div>

                                    @if($homeClub)
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $homeClub->logo_url }}"
                                                 alt="{{ $homeClub->name }}"
                                                 class="club-logo-small">

                                            <span class="fw-bold">
                                                {{ $homeClub->name }}
                                            </span>
                                        </div>

                                        @if($homeSource)
                                            <div class="small text-muted mt-2">
                                                {{ $homeSource }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="automatic-team-placeholder-icon">
                                                ?
                                            </span>

                                            <span class="fw-bold text-muted">
                                                {{ $homePlaceholder }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div class="automatic-match-separator">
                                    contre
                                </div>

                                <div class="automatic-team-slot {{ $awayClub ? '' : 'automatic-team-slot-pending' }}">
                                    <div class="text-uppercase text-muted fw-bold small mb-2">
                                        {{ $awayLabel }}
                                    </div>

                                    @if($awayClub)
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $awayClub->logo_url }}"
                                                 alt="{{ $awayClub->name }}"
                                                 class="club-logo-small">

                                            <span class="fw-bold">
                                                {{ $awayClub->name }}
                                            </span>
                                        </div>

                                        @if($awaySource)
                                            <div class="small text-muted mt-2">
                                                {{ $awaySource }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="automatic-team-placeholder-icon">
                                                ?
                                            </span>

                                            <span class="fw-bold text-muted">
                                                {{ $awayPlaceholder }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($positionIsOccupied && $existingMatch)
                                <div class="alert alert-danger mt-3 mb-0">
                                    La position {{ $pair['position'] }} est déjà occupée par
                                    <strong>{{ $existingMatch->homeClub->name }} - {{ $existingMatch->awayClub->name }}</strong>.
                                    Le match automatique n’est pas créé afin de ne rien remplacer.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($automaticReadyPairs->isNotEmpty() && ! $preparationIsLocked)
                <form method="POST"
                      action="{{ route('admin.seasons.journees.matches.storeBulk', $matchesRouteParameters) }}">
                    @csrf

                    @if($fromUpcomingMatches)
                        <input type="hidden"
                               name="from"
                               value="upcoming-matches">
                    @endif

                    @foreach($automaticReadyPairs as $pair)
                        <input type="hidden"
                               name="clubs[]"
                               value="{{ $pair['home']->id }}">

                        <input type="hidden"
                               name="clubs[]"
                               value="{{ $pair['away']->id }}">

                        <input type="hidden"
                               name="positions[]"
                               value="{{ $pair['position'] }}">
                    @endforeach

                    <button class="btn btn-warning rounded-pill fw-bold px-4">
                        Créer automatiquement
                        {{ $automaticReadyPairs->count() > 1
                            ? 'les '.$automaticReadyPairs->count().' matchs disponibles'
                            : 'le match disponible' }}
                    </button>
                </form>
            @elseif($automaticCreatedPairsCount === $automaticPairs->count())
                <div class="alert alert-success mb-0">
                    Tous les matchs proposés automatiquement sont déjà créés.
                </div>
            @elseif($automaticOccupiedPairsCount > 0)
                <div class="alert alert-warning mb-0">
                    Une ou plusieurs positions sont déjà occupées par des matchs différents.
                    Aucun match existant ne sera remplacé automatiquement.
                </div>
            @elseif(! $preparationIsLocked)
                <div class="alert alert-info mb-0">
                    @if($automaticKnownTeamsCount === 1)
                        L’équipe déjà déterminée reste affichée automatiquement.
                    @elseif($automaticKnownTeamsCount > 1)
                        Les équipes déjà déterminées restent affichées automatiquement.
                    @else
                        Les emplacements des matchs sont prêts à être alimentés automatiquement.
                    @endif

                    Chaque match sera proposé dès que ses deux équipes seront connues, sans attendre les autres matchs du tour.
                </div>
            @endif
        @endif
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
                <div class="alert alert-info mb-0">
                    Aucun duo de clubs disponible pour créer un match sur cette journée.
                </div>
            @else
                <div id="availableClubs"
                     class="club-picker">
                    @foreach($availableClubs as $club)
                        <button type="button"
                                class="club-picker-item"
                                data-club-id="{{ $club->id }}"
                                data-club-name="{{ $club->name }}"
                                @disabled($preparationIsLocked)>
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

                @unless($preparationIsLocked)
                    <button type="button"
                            id="resetSelection"
                            class="btn btn-sm btn-outline-secondary rounded-pill">
                        Réinitialiser
                    </button>
                @endunless
            </div>

            @if($preparationIsLocked)
                <div class="alert alert-info mb-0">
                    La création de matchs est désactivée.
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.seasons.journees.matches.storeBulk', $matchesRouteParameters) }}"
                      id="bulkMatchesForm">
                    @csrf

                    @if($fromUpcomingMatches)
                        <input type="hidden"
                               name="from"
                               value="upcoming-matches">
                    @endif

                    <div id="selectedClubsInputs"></div>

                    <div id="draftMatches"
                         class="draft-matches">
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
            @endif
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
                <ul id="matchesList"
                    class="list-group">
                    @foreach($matches as $match)
                        <li class="list-group-item d-flex justify-content-between align-items-center"
                            data-id="{{ $match->id }}">
                            <div class="d-flex align-items-center gap-3">
                                @if($preparationIsLocked)
                                    <span class="text-muted">
                                        ☰
                                    </span>
                                @else
                                    <span class="drag-handle text-muted"
                                          style="cursor: grab;">
                                        ☰
                                    </span>
                                @endif

                                <div class="fw-bold">
                                    {{ $match->homeClub->name }}
                                    <span class="text-muted mx-1">—</span>
                                    {{ $match->awayClub->name }}
                                </div>
                            </div>

                            @unless($preparationIsLocked)
                                <form method="POST"
                                      action="{{ route('admin.matches.destroy', $destroyRouteParameters($match)) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger rounded-pill">
                                        Supprimer
                                    </button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .automatic-match-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: stretch;
        gap: 0.75rem;
    }

    .automatic-team-slot {
        min-width: 0;
        padding: 0.9rem;
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 0.9rem;
    }

    .automatic-team-slot-pending {
        border-style: dashed;
        background: rgba(255, 255, 255, 0.55);
    }

    .automatic-match-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-secondary-color);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .automatic-team-placeholder-icon {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 1px dashed var(--bs-secondary-color);
        border-radius: 50%;
        color: var(--bs-secondary-color);
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .automatic-match-grid {
            grid-template-columns: 1fr;
        }

        .automatic-match-separator {
            padding: 0.1rem 0;
        }
    }
</style>
@endpush

@unless($preparationIsLocked)
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

            function clearChildren(element) {
                while (element.firstChild) {
                    element.removeChild(element.firstChild);
                }
            }

            function createAlert(message, type = 'info') {
                const alert = document.createElement('div');

                alert.className = `alert alert-${type} mb-0`;
                alert.textContent = message;

                return alert;
            }

            function createSelectedClubButton(club, index) {
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'selected-club-chip';
                button.textContent = club.name;

                button.addEventListener('click', function () {
                    removeSelectedClub(index);
                });

                return button;
            }

            function renderDraftMatches() {
                clearChildren(selectedClubsInputs);
                clearChildren(draftMatches);

                selectedClubs.forEach(function (club) {
                    const input = document.createElement('input');

                    input.type = 'hidden';
                    input.name = 'clubs[]';
                    input.value = club.id;

                    selectedClubsInputs.appendChild(input);
                });

                if (selectedClubs.length === 0) {
                    draftMatches.appendChild(
                        createAlert('Aucun club sélectionné pour le moment.')
                    );
                } else {
                    const list = document.createElement('div');

                    list.className = 'list-group';

                    for (let i = 0; i < selectedClubs.length; i += 2) {
                        const home = selectedClubs[i];
                        const away = selectedClubs[i + 1];
                        const item = document.createElement('div');
                        const title = document.createElement('div');
                        const clubsWrapper = document.createElement('div');

                        item.className = 'list-group-item';

                        title.className = 'fw-bold mb-2';
                        title.textContent = `Match ${Math.floor(i / 2) + 1}`;

                        clubsWrapper.className = 'd-flex flex-wrap gap-2';
                        clubsWrapper.appendChild(
                            createSelectedClubButton(home, i)
                        );

                        if (away) {
                            clubsWrapper.appendChild(
                                createSelectedClubButton(away, i + 1)
                            );
                        } else {
                            const pending = document.createElement('span');

                            pending.className = 'text-muted';
                            pending.textContent = 'En attente...';
                            clubsWrapper.appendChild(pending);
                        }

                        item.appendChild(title);
                        item.appendChild(clubsWrapper);
                        list.appendChild(item);
                    }

                    draftMatches.appendChild(list);

                    if (selectedClubs.length % 2 !== 0) {
                        const warning = createAlert(
                            'Sélectionne encore un club pour compléter le dernier match.',
                            'warning'
                        );

                        warning.classList.add('mt-3');
                        draftMatches.appendChild(warning);
                    }
                }

                saveMatchesButton.disabled =
                    selectedClubs.length < 2 || selectedClubs.length % 2 !== 0;

                resetSelection.disabled = selectedClubs.length === 0;

                if (availableCount && availableClubs) {
                    availableCount.textContent = availableClubs
                        .querySelectorAll('.club-picker-item:not(.d-none)')
                        .length;
                }
            }

            function resetDraft() {
                selectedClubs.length = 0;

                document.querySelectorAll('.club-picker-item').forEach(function (button) {
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

            document.querySelectorAll('.club-picker-item').forEach(function (button) {
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
                            .map(function (item) {
                                return item.dataset.id;
                            });

                        fetch("{{ route('admin.seasons.journees.matches.reorder', $matchesRouteParameters) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ matches: matches }),
                        });
                    },
                });
            });
        </script>
    @endpush
@endunless
