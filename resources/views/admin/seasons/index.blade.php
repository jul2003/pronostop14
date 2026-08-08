@extends('layouts.pronos')

@section('content')

@php
    $activeSeason = $seasons->firstWhere('is_active', true);

    $historicalSeasons = $seasons
        ->filter(fn ($season) => ! $season->is_active)
        ->values();

    $seasonsWithJournees = $seasons
        ->filter(fn ($season) => (int) $season->journees_count > 0)
        ->values();
@endphp

@include('admin.partials.back-link')

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Saisons
        </h2>

        <p class="text-muted mb-0">
            Gère les saisons du championnat.
        </p>
    </div>

    <a href="{{ route('admin.seasons.create') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Ajouter une saison
    </a>
</div>

<div class="mb-4">
    <h3 class="h5 fw-bold mb-3">
        Saison active
    </h3>

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Saison</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Verrouillage</th>
                        <th class="text-center">Journées</th>
                        <th class="text-center">TOP 14</th>
                        <th class="text-center">PRO D2</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @if($activeSeason)
                        <tr>
                            <td class="fw-bold">
                                {{ $activeSeason->name }}
                            </td>

                            <td class="text-center">
                                <span class="badge text-bg-success rounded-pill">
                                    Active
                                </span>
                            </td>

                            <td class="text-center">
                                @if($activeSeason->is_locked)
                                    <span class="badge text-bg-danger rounded-pill">
                                        Verrouillée
                                    </span>
                                @else
                                    <span class="badge text-bg-light border text-dark rounded-pill">
                                        Ouverte
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->journees_count }}
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->top14_clubs_count }}
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->prod2_clubs_count }}
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.seasons.active.edit') }}"
                                       class="btn btn-sm btn-outline-dark rounded-pill">
                                        Modifier
                                    </a>

                                    @if($activeSeason->is_locked)
                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Joueurs
                                        </span>

                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Clubs
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.active.players') }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Joueurs
                                        </a>

                                        <a href="{{ route('admin.seasons.active.clubs') }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Clubs
                                        </a>
                                    @endif

                                    <a href="{{ route('admin.seasons.active.journees') }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Journées
                                    </a>

                                    @if($activeSeason->is_locked)
                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Barème
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Avant-saison
                                        </span>

                                        @if((int) $activeSeason->journees_count > 0)
                                            <span class="btn btn-sm btn-outline-danger rounded-pill disabled opacity-50">
                                                Supprimer journées
                                            </span>
                                        @else
                                            <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                                Générer journées
                                            </span>
                                        @endif
                                    @else
                                        <a href="{{ route('admin.seasons.active.scoring.edit') }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Barème
                                        </a>

                                        <a href="{{ route('admin.seasons.active.preseason.edit') }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Avant-saison
                                        </a>

                                        @if((int) $activeSeason->journees_count === 0)
                                            <form method="POST"
                                                  action="{{ route('admin.seasons.generateJournees', $activeSeason) }}">
                                                @csrf

                                                <button class="btn btn-sm btn-outline-success rounded-pill">
                                                    Générer journées
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteJourneesModal{{ $activeSeason->id }}">
                                                Supprimer journées
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="7"
                                class="text-center text-muted py-4">
                                Aucune saison active définie.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div>
    <h3 class="h5 fw-bold mb-3">
        Historique des saisons
    </h3>

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Saison</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Verrouillage</th>
                        <th class="text-center">Journées</th>
                        <th class="text-center">TOP 14</th>
                        <th class="text-center">PRO D2</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($historicalSeasons as $season)
                        <tr>
                            <td class="fw-bold">
                                {{ $season->name }}
                            </td>

                            <td class="text-center">
                                <span class="badge text-bg-secondary rounded-pill">
                                    Inactive
                                </span>
                            </td>

                            <td class="text-center">
                                @if($season->is_locked)
                                    <span class="badge text-bg-danger rounded-pill">
                                        Verrouillée
                                    </span>
                                @else
                                    <span class="badge text-bg-light border text-dark rounded-pill">
                                        Ouverte
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $season->journees_count }}
                            </td>

                            <td class="text-center">
                                {{ $season->top14_clubs_count }}
                            </td>

                            <td class="text-center">
                                {{ $season->prod2_clubs_count }}
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.seasons.edit', $season) }}"
                                       class="btn btn-sm btn-outline-dark rounded-pill">
                                        Modifier
                                    </a>

                                    @if($season->is_locked)
                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Joueurs
                                        </span>

                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Clubs
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.players', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Joueurs
                                        </a>

                                        <a href="{{ route('admin.seasons.clubs', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Clubs
                                        </a>
                                    @endif

                                    <a href="{{ route('admin.seasons.journees', $season) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Journées
                                    </a>

                                    @if($season->is_locked)
                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Barème
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Avant-saison
                                        </span>

                                        @if((int) $season->journees_count > 0)
                                            <span class="btn btn-sm btn-outline-danger rounded-pill disabled opacity-50">
                                                Supprimer journées
                                            </span>
                                        @else
                                            <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                                Générer journées
                                            </span>
                                        @endif
                                    @else
                                        <a href="{{ route('admin.seasons.scoring.edit', $season) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Barème
                                        </a>

                                        <a href="{{ route('admin.seasons.preseason.edit', $season) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Avant-saison
                                        </a>

                                        @if((int) $season->journees_count === 0)
                                            <form method="POST"
                                                  action="{{ route('admin.seasons.generateJournees', $season) }}">
                                                @csrf

                                                <button class="btn btn-sm btn-outline-success rounded-pill">
                                                    Générer journées
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteJourneesModal{{ $season->id }}">
                                                Supprimer journées
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                class="text-center text-muted py-4">
                                Aucune saison historique pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($seasonsWithJournees as $seasonWithJournees)
    <div class="modal fade"
         id="deleteJourneesModal{{ $seasonWithJournees->id }}"
         tabindex="-1"
         aria-labelledby="deleteJourneesModalLabel{{ $seasonWithJournees->id }}"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST"
                  action="{{ route('admin.seasons.generateJournees', $seasonWithJournees) }}"
                  class="modal-content border-0 shadow rounded-4"
                  autocomplete="off"
                  data-delete-journees-form>
                @csrf

                <input type="hidden"
                       name="journees_action"
                       value="delete">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="text-uppercase text-danger fw-bold small mb-1">
                            Réinitialisation du calendrier
                        </div>

                        <h2 class="modal-title h5 fw-bold mb-0"
                            id="deleteJourneesModalLabel{{ $seasonWithJournees->id }}">
                            Supprimer les journées de {{ $seasonWithJournees->name }} ?
                        </h2>
                    </div>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fermer">
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger">
                        <div class="fw-bold mb-1">
                            Action irréversible
                        </div>

                        Cette opération supprimera
                        <strong>{{ $seasonWithJournees->journees_count }} journée(s)</strong>
                        et
                        <strong>{{ $seasonWithJournees->matches_count }} match(s)</strong>
                        de cette saison.
                    </div>

                    <div class="border rounded-4 p-3 mb-3 bg-light">
                        <div class="fw-bold mb-2">
                            Seront supprimés
                        </div>

                        <ul class="small text-muted mb-3 ps-3">
                            <li>les journées régulières et les phases finales ;</li>
                            <li>les dates, états de saisie et matchs associés ;</li>
                            <li>les éventuelles exceptions de date des matchs.</li>
                        </ul>

                        <div class="fw-bold mb-2">
                            Seront conservés
                        </div>

                        <ul class="small text-muted mb-0 ps-3">
                            <li>la saison et son statut ;</li>
                            <li>les clubs et les joueurs sélectionnés ;</li>
                            <li>les barèmes et la configuration avant-saison.</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        La suppression est automatiquement refusée si des pronostics,
                        scores ou résultats ont déjà été enregistrés pour cette saison.
                    </div>

                    <label class="form-label fw-bold"
                           for="deleteJourneesConfirmation{{ $seasonWithJournees->id }}">
                        Pour confirmer, saisis exactement :
                        <span class="text-danger">
                            {{ $seasonWithJournees->name }}
                        </span>
                    </label>

                    <input type="text"
                           id="deleteJourneesConfirmation{{ $seasonWithJournees->id }}"
                           name="confirmation_name"
                           class="form-control"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false"
                           data-delete-journees-confirmation
                           data-expected-name="{{ $seasonWithJournees->name }}"
                           required>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <button type="submit"
                            class="btn btn-danger rounded-pill fw-bold px-4"
                            data-delete-journees-submit
                            disabled>
                        Supprimer les journées
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document
            .querySelectorAll('[data-delete-journees-form]')
            .forEach(function (form) {
                const input = form.querySelector(
                    '[data-delete-journees-confirmation]'
                );

                const submitButton = form.querySelector(
                    '[data-delete-journees-submit]'
                );

                const modal = form.closest('.modal');

                if (!input || !submitButton) {
                    return;
                }

                const expectedName = input.dataset.expectedName || '';

                function refreshSubmitButton() {
                    submitButton.disabled =
                        input.value.trim() !== expectedName;
                }

                input.addEventListener(
                    'input',
                    refreshSubmitButton
                );

                modal?.addEventListener(
                    'shown.bs.modal',
                    function () {
                        refreshSubmitButton();
                        input.focus();
                    }
                );

                modal?.addEventListener(
                    'hidden.bs.modal',
                    function () {
                        input.value = '';
                        refreshSubmitButton();
                    }
                );

                refreshSubmitButton();
            });
    });
</script>
@endpush
