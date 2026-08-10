@extends('layouts.pronos')

@section('content')

@php
    $hasOpenMatches = $matches->contains(fn ($match) => ! $match->isPredictionLocked());
    $predictionNotice = $predictionNotice ?? null;
    $predictionWarning = session('prediction_warning');
    $rankingIsAvailable = $rankingIsAvailable ?? false;
    $previousJournee = $previousJournee ?? null;
    $nextJournee = $nextJournee ?? null;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Mes pronostics
        </div>

        <h2 class="fw-bold mb-1">
            {{ $journee->name }}
        </h2>

        <p class="text-muted mb-0">
            {{ $season->name }}

            @if($journee->first_match_at)
                · premier match : {{ $journee->first_match_at->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2 prono-page-header-actions">
        <a href="{{ route('pronos.index') }}"
           class="btn btn-outline-secondary rounded-pill fw-bold px-4">
            ← Retour aux journées
        </a>

        <a href="{{ route('results.index') }}"
           class="btn btn-outline-primary rounded-pill fw-bold px-4">
            Résultats & points
        </a>

        @if($rankingIsAvailable)
            <a href="{{ route('rankings.journee', [$season, $journee]) }}"
               class="btn btn-warning rounded-pill fw-bold px-4">
                Classement journée
            </a>
        @endif
    </div>
</div>

@if($predictionWarning)
    <div class="alert alert-warning">
        {{ $predictionWarning }}
    </div>
@elseif($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@elseif($predictionNotice)
    <div class="alert alert-{{ $predictionNotice['type'] }}">
        {{ $predictionNotice['message'] }}
    </div>
@endif

@if($previousJournee || $nextJournee || ($hasOpenMatches && $matches->isNotEmpty()))
    <div class="prono-day-actions mb-4">
        <div class="prono-day-actions-previous">
            @if($previousJournee)
                <a href="{{ route('pronos.show', [$season, $previousJournee]) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4 js-prono-navigation-link"
                   data-navigation-name="{{ $previousJournee->name }}"
                   title="{{ $previousJournee->name }}">
                    ← Journée précédente : {{ $previousJournee->name }}
                </a>
            @endif
        </div>

        <div class="prono-day-actions-save">
            @if($hasOpenMatches && $matches->isNotEmpty())
                <button type="submit"
                        form="pronoEntryForm"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer mes pronostics
                </button>
            @endif
        </div>

        <div class="prono-day-actions-next">
            @if($nextJournee)
                <a href="{{ route('pronos.show', [$season, $nextJournee]) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4 js-prono-navigation-link"
                   data-navigation-name="{{ $nextJournee->name }}"
                   title="{{ $nextJournee->name }}">
                    Journée suivante : {{ $nextJournee->name }} →
                </a>
            @endif
        </div>
    </div>
@endif

@if($matches->isEmpty())
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucun match disponible pour cette journée.
        </div>
    </div>
@else
    <form method="POST"
          id="pronoEntryForm"
          action="{{ route('pronos.store', [$season, $journee]) }}"
          autocomplete="off">
        @csrf

        <div class="rugby-card p-0 overflow-hidden pronos-entry-card">
            <div class="table-responsive pronos-entry-table-wrapper">
                <table class="table table-hover align-middle mb-0 prono-table pronos-entry-table">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center">Résultat</th>
                            <th class="text-center">Essais</th>
                            <th class="text-center">Bonus dom.</th>
                            <th class="text-center">Bonus ext.</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matches as $match)
                            @php
                                $prono = $match->pronos->first();

                                $matchIsLocked = $match->isPredictionLocked();
                                $matchDeadline = $match->effectivePredictionDeadline();
                                $hasException = $match->hasPredictionDeadlineException();

                                $resultValue = old(
                                    "pronos.{$match->id}.predicted_result",
                                    $prono?->predicted_result
                                );

                                $triesValue = old(
                                    "pronos.{$match->id}.predicted_tries",
                                    $prono?->predicted_tries
                                );

                                $homeBonusValue = old(
                                    "pronos.{$match->id}.predicted_home_bonus",
                                    $prono?->predicted_home_bonus
                                );

                                $awayBonusValue = old(
                                    "pronos.{$match->id}.predicted_away_bonus",
                                    $prono?->predicted_away_bonus
                                );
                            @endphp

                            <tr class="{{ $matchIsLocked ? 'table-light' : '' }}">
                                <td class="match-cell">
                                    <div class="match-line">
                                        <div class="match-home">
                                            <img src="{{ $match->homeClub->logo_url }}"
                                                 alt="{{ $match->homeClub->name }}"
                                                 class="club-logo-small">

                                            <span>
                                                {{ $match->homeClub->name }}
                                            </span>
                                        </div>

                                        <div class="match-separator">
                                            -
                                        </div>

                                        <div class="match-away">
                                            <img src="{{ $match->awayClub->logo_url }}"
                                                 alt="{{ $match->awayClub->name }}"
                                                 class="club-logo-small">

                                            <span>
                                                {{ $match->awayClub->name }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($hasException)
                                        <div class="match-deadline-line mt-1">
                                            <span class="badge rounded-pill text-bg-warning">
                                                Date limite exceptionnelle
                                            </span>

                                            @if($matchDeadline)
                                                <span class="small text-muted">
                                                    Prono jusqu’au {{ $matchDeadline->format('d/m/Y H:i') }}
                                                </span>
                                            @endif

                                            @if($matchIsLocked)
                                                <span class="badge rounded-pill text-bg-secondary">
                                                    Verrouillé
                                                </span>
                                            @endif
                                        </div>
                                    @elseif($matchIsLocked)
                                        <div class="match-deadline-line mt-1">
                                            <span class="badge rounded-pill text-bg-secondary">
                                                Verrouillé
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td class="text-center prono-mobile-field prono-result-cell"
                                    data-mobile-label="Résultat">
                                    <div class="prono-choice-group"
                                         role="group"
                                         aria-label="Résultat du match {{ $match->homeClub->name }} contre {{ $match->awayClub->name }}">
                                        @foreach($journee->resultOptionShortLabels() as $value => $label)
                                            <input type="radio"
                                                   id="predicted_result_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_result]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input js-prono-dirty-track"
                                                   data-saved-checked="{{ $prono?->predicted_result === $value ? '1' : '0' }}"
                                                   @checked($resultValue === $value)
                                                   @disabled($matchIsLocked)
                                                   @if(! $matchIsLocked) required @endif>

                                            <label for="predicted_result_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label"
                                                   title="{{ $journee->resultOptionLabel($value) }}">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center prono-mobile-field prono-tries-cell"
                                    data-mobile-label="Essais">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="pronos[{{ $match->id }}][predicted_tries]"
                                           value="{{ $triesValue }}"
                                           class="form-control form-control-sm prono-tries-input mx-auto js-prono-dirty-track"
                                           data-saved-value="{{ $prono?->predicted_tries ?? '' }}"
                                           autocomplete="off"
                                           autocorrect="off"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           aria-label="Nombre d’essais pour {{ $match->homeClub->name }} contre {{ $match->awayClub->name }}"
                                           @disabled($matchIsLocked)
                                           @if(! $matchIsLocked) required @endif>
                                </td>

                                <td class="text-center prono-mobile-field prono-home-bonus-cell"
                                    data-mobile-label="Bonus dom.">
                                    <div class="prono-choice-group bonus-choice-group"
                                         role="group"
                                         aria-label="Bonus domicile pour {{ $match->homeClub->name }} contre {{ $match->awayClub->name }}">
                                        @foreach(['o' => 'o', '-' => '-', 'd' => 'd'] as $value => $label)
                                            <input type="radio"
                                                   id="predicted_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_home_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input bonus-choice-input js-prono-dirty-track"
                                                   data-saved-checked="{{ $prono?->predicted_home_bonus === $value ? '1' : '0' }}"
                                                   @checked($homeBonusValue === $value)
                                                   @disabled($matchIsLocked)>

                                            <label for="predicted_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label bonus-choice-label"
                                                   title="Clique une deuxième fois pour enlever le choix">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center prono-mobile-field prono-away-bonus-cell"
                                    data-mobile-label="Bonus ext.">
                                    <div class="prono-choice-group bonus-choice-group"
                                         role="group"
                                         aria-label="Bonus extérieur pour {{ $match->homeClub->name }} contre {{ $match->awayClub->name }}">
                                        @foreach(['o' => 'o', '-' => '-', 'd' => 'd'] as $value => $label)
                                            <input type="radio"
                                                   id="predicted_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_away_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input bonus-choice-input js-prono-dirty-track"
                                                   data-saved-checked="{{ $prono?->predicted_away_bonus === $value ? '1' : '0' }}"
                                                   @checked($awayBonusValue === $value)
                                                   @disabled($matchIsLocked)>

                                            <label for="predicted_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label bonus-choice-label"
                                                   title="Clique une deuxième fois pour enlever le choix">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center prono-action-cell {{ $prono ? '' : 'prono-action-empty' }}">
                                    @if($prono && ! $matchIsLocked)
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm rounded-pill fw-bold px-3"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deletePronoModal{{ $match->id }}"
                                                aria-haspopup="dialog">
                                            Effacer
                                        </button>
                                    @elseif($prono)
                                        <span class="small text-muted">
                                            Verrouillé
                                        </span>
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    @foreach($matches as $match)
        @php
            $prono = $match->pronos->first();
            $matchIsLocked = $match->isPredictionLocked();
        @endphp

        @if($prono && ! $matchIsLocked)
            <div class="modal fade prono-delete-modal"
                 id="deletePronoModal{{ $match->id }}"
                 tabindex="-1"
                 aria-labelledby="deletePronoModalLabel{{ $match->id }}"
                 aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <div class="text-uppercase text-danger fw-bold small mb-1">
                                    Effacer un pronostic
                                </div>

                                <h2 class="modal-title h5 fw-bold mb-0"
                                    id="deletePronoModalLabel{{ $match->id }}">
                                    Confirmer l’effacement
                                </h2>
                            </div>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer">
                            </button>
                        </div>

                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                Tu vas effacer le pronostic enregistré pour ce match :
                            </p>

                            <div class="prono-delete-match">
                                <div class="prono-delete-team">
                                    <img src="{{ $match->homeClub->logo_url }}"
                                         alt="{{ $match->homeClub->name }}"
                                         class="prono-delete-logo">

                                    <span class="fw-bold">
                                        {{ $match->homeClub->name }}
                                    </span>
                                </div>

                                <div class="fw-bold text-muted">
                                    -
                                </div>

                                <div class="prono-delete-team">
                                    <img src="{{ $match->awayClub->logo_url }}"
                                         alt="{{ $match->awayClub->name }}"
                                         class="prono-delete-logo">

                                    <span class="fw-bold">
                                        {{ $match->awayClub->name }}
                                    </span>
                                </div>
                            </div>

                            <p class="small text-muted mt-3 mb-3">
                                Tu pourras saisir de nouveau ce pronostic tant que le match reste ouvert.
                            </p>

                            <div class="alert alert-warning mb-0">
                                <div class="fw-bold mb-1">
                                    Attention
                                </div>

                                Les autres modifications effectuées sur cette journée mais pas encore
                                enregistrées seront perdues.
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button"
                                    class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                                    data-bs-dismiss="modal">
                                Annuler
                            </button>

                            <form method="POST"
                                  action="{{ route('pronos.store', [$season, $journee]) }}"
                                  class="m-0">
                                @csrf

                                <input type="hidden"
                                       name="delete_prono_match_id"
                                       value="{{ $match->id }}">

                                <button type="submit"
                                        class="btn btn-danger rounded-pill fw-bold px-4">
                                    Effacer le pronostic
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif

@if($previousJournee || $nextJournee)
    <button type="button"
            id="openUnsavedPronoNavigationModal"
            class="d-none"
            data-bs-toggle="modal"
            data-bs-target="#unsavedPronoNavigationModal"
            aria-hidden="true"
            tabindex="-1">
    </button>

    <div class="modal fade prono-navigation-modal"
         id="unsavedPronoNavigationModal"
         tabindex="-1"
         aria-labelledby="unsavedPronoNavigationModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="text-uppercase text-warning fw-bold small mb-1">
                            Modifications non enregistrées
                        </div>

                        <h2 class="modal-title h5 fw-bold mb-0"
                            id="unsavedPronoNavigationModalLabel">
                            Enregistrer avant de changer de journée ?
                        </h2>
                    </div>

                    <button type="button"
                            id="dismissUnsavedPronoNavigationModal"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fermer">
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Tu as modifié un ou plusieurs pronostics sur
                        <strong>{{ $journee->name }}</strong> sans les enregistrer.
                    </p>

                    <div class="prono-navigation-destination">
                        <div class="text-uppercase text-muted fw-bold small mb-1">
                            Destination
                        </div>

                        <div class="fw-bold"
                             id="unsavedPronoNavigationDestination">
                            Journée suivante
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0">
                        En continuant sans enregistrer, les dernières modifications de cette journée seront perdues.
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 prono-navigation-modal-actions">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                            data-bs-dismiss="modal">
                        Rester ici
                    </button>

                    <button type="button"
                            id="continuePronoNavigationWithoutSaving"
                            class="btn btn-outline-danger rounded-pill fw-bold px-4">
                        Continuer sans enregistrer
                    </button>

                    <button type="button"
                            id="savePronoAndContinueNavigation"
                            class="btn btn-warning rounded-pill fw-bold px-4">
                        Enregistrer puis continuer
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('styles')
<style>
    .prono-day-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 0.75rem;
    }

    .prono-day-actions-previous {
        justify-self: start;
        min-width: 0;
    }

    .prono-day-actions-save {
        justify-self: center;
    }

    .prono-day-actions-next {
        justify-self: end;
        min-width: 0;
    }

    .prono-day-actions .btn {
        white-space: nowrap;
    }

    .pronos-entry-table th,
    .pronos-entry-table td {
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }

    .pronos-entry-table .match-cell {
        min-width: 360px;
    }

    .pronos-entry-table .prono-action-cell {
        min-width: 110px;
    }

    .pronos-entry-table .match-home span,
    .pronos-entry-table .match-away span {
        white-space: nowrap;
    }

    .pronos-entry-table .match-line {
        grid-template-columns: minmax(120px, 1fr) 24px minmax(120px, 1fr);
    }

    .match-deadline-line {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
    }

    .bonus-choice-label {
        cursor: pointer;
    }

    .prono-delete-modal .modal-content,
    .prono-navigation-modal .modal-content {
        border-radius: 1.25rem;
    }

    .prono-delete-match {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.025);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 1rem;
    }

    .prono-delete-team {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-width: 0;
        text-align: center;
    }

    .prono-delete-logo {
        width: 42px;
        height: 42px;
        object-fit: contain;
    }

    .prono-navigation-destination {
        padding: 1rem;
        background: rgba(13, 110, 253, 0.05);
        border: 1px solid rgba(13, 110, 253, 0.18);
        border-radius: 1rem;
    }

    @media (max-width: 991.98px) {
        .prono-day-actions {
            grid-template-columns: 1fr;
        }

        .prono-day-actions-previous,
        .prono-day-actions-save,
        .prono-day-actions-next {
            justify-self: stretch;
        }

        .prono-day-actions .btn {
            width: 100%;
            white-space: normal;
        }
    }

    @media (max-width: 767.98px) {
        .prono-page-header-actions {
            width: 100%;
        }

        .prono-page-header-actions .btn {
            flex: 1 1 100%;
            width: 100%;
        }

        .pronos-entry-card {
            overflow: visible !important;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .pronos-entry-table-wrapper {
            overflow: visible;
        }

        .pronos-entry-table,
        .pronos-entry-table tbody {
            display: block;
            width: 100%;
        }

        .pronos-entry-table thead {
            display: none;
        }

        .pronos-entry-table tbody {
            display: grid;
            gap: 1rem;
        }

        .pronos-entry-table tbody tr {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem 0.75rem;
            width: 100%;
            padding: 1rem;
            background: #ffffff;
            border: 1px solid rgba(6, 20, 47, 0.12);
            border-radius: 1.1rem;
            box-shadow: 0 0.45rem 1.1rem rgba(6, 20, 47, 0.08);
        }

        .pronos-entry-table tbody tr.table-light {
            background: #f8f9fa;
        }

        .pronos-entry-table tbody td {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            width: auto;
            min-width: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            white-space: normal;
        }

        .pronos-entry-table .match-cell {
            grid-column: 1 / -1;
            width: 100%;
            padding-bottom: 0.85rem !important;
            border-bottom: 1px solid rgba(6, 20, 47, 0.1) !important;
        }

        .pronos-entry-table .match-line {
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            width: 100%;
            gap: 0.45rem;
        }

        .pronos-entry-table .match-home,
        .pronos-entry-table .match-away {
            flex-direction: column;
            justify-content: center;
            gap: 0.35rem;
            min-width: 0;
            text-align: center;
        }

        .pronos-entry-table .match-home span,
        .pronos-entry-table .match-away span {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
            font-size: 0.88rem;
            line-height: 1.15;
        }

        .pronos-entry-table .club-logo-small {
            width: 38px;
            height: 38px;
        }

        .pronos-entry-table .match-separator {
            align-self: center;
            font-size: 1rem;
        }

        .match-deadline-line {
            justify-content: center;
            text-align: center;
        }

        .prono-mobile-field::before {
            content: attr(data-mobile-label);
            display: block;
            color: var(--bs-secondary-color);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1;
            text-transform: uppercase;
        }

        .prono-mobile-field .prono-choice-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
        }

        .prono-mobile-field .prono-choice-label {
            min-width: 40px;
            padding: 0.38rem 0.62rem;
            font-size: 0.86rem;
        }

        .prono-mobile-field .prono-tries-input {
            width: 72px;
            min-height: 38px;
            font-size: 1rem;
        }

        .prono-action-cell {
            grid-column: 1 / -1;
            padding-top: 0.15rem !important;
        }

        .prono-action-cell .btn {
            min-width: 130px;
        }

        .prono-action-empty {
            display: none !important;
        }

        .prono-navigation-modal-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .prono-navigation-modal-actions .btn {
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .prono-delete-match {
            gap: 0.5rem;
            padding: 0.75rem;
        }

        .prono-delete-team {
            font-size: 0.875rem;
        }

        .prono-delete-logo {
            width: 34px;
            height: 34px;
        }

        .prono-delete-modal .modal-footer {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .prono-delete-modal .modal-footer .btn,
        .prono-delete-modal .modal-footer form {
            width: 100%;
        }

        .prono-delete-modal .modal-footer form .btn {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pronoEntryForm = document.getElementById('pronoEntryForm');

        const triesInputs = Array.from(
            document.querySelectorAll(
                '#pronoEntryForm .prono-tries-input'
            )
        ).filter(function (input) {
            return !input.disabled;
        });

        const navigationLinks = document.querySelectorAll(
            '.js-prono-navigation-link'
        );

        const navigationModalElement = document.getElementById(
            'unsavedPronoNavigationModal'
        );

        const navigationModalOpenButton = document.getElementById(
            'openUnsavedPronoNavigationModal'
        );

        const navigationModalDismissButton = document.getElementById(
            'dismissUnsavedPronoNavigationModal'
        );

        const navigationDestination = document.getElementById(
            'unsavedPronoNavigationDestination'
        );

        const continueWithoutSavingButton = document.getElementById(
            'continuePronoNavigationWithoutSaving'
        );

        const saveAndContinueButton = document.getElementById(
            'savePronoAndContinueNavigation'
        );

        const navigationStorageKey = @json('pronostop14:prono-navigation:'.auth()->id().':'.$season->id.':'.$journee->id);
        const pronoSaveSucceeded = @json(session('success') === 'Pronostics enregistrés.');

        let pendingNavigation = null;
        let navigationToStoreOnSubmit = null;

        function removeStoredNavigation() {
            try {
                window.sessionStorage.removeItem(
                    navigationStorageKey
                );
            } catch (error) {
                // La navigation reste utilisable même si le stockage est indisponible.
            }
        }

        function storeNavigationAfterSubmit(navigation) {
            try {
                window.sessionStorage.setItem(
                    navigationStorageKey,
                    JSON.stringify(navigation)
                );
            } catch (error) {
                // Le formulaire sera tout de même enregistré sur la journée courante.
            }
        }

        function readStoredNavigation() {
            try {
                const storedValue = window.sessionStorage.getItem(
                    navigationStorageKey
                );

                return storedValue
                    ? JSON.parse(storedValue)
                    : null;
            } catch (error) {
                return null;
            }
        }

        const storedNavigation = readStoredNavigation();

        if (storedNavigation) {
            removeStoredNavigation();

            const storedNavigationIsRecent =
                Number.isFinite(storedNavigation.submittedAt)
                && Date.now() - storedNavigation.submittedAt < 300000;

            if (
                pronoSaveSucceeded
                && storedNavigationIsRecent
                && typeof storedNavigation.url === 'string'
                && storedNavigation.url !== ''
            ) {
                window.location.assign(storedNavigation.url);

                return;
            }
        }

        function hasUnsavedPronoChanges() {
            if (!pronoEntryForm) {
                return false;
            }

            return Array.from(
                pronoEntryForm.querySelectorAll(
                    '.js-prono-dirty-track'
                )
            ).some(function (input) {
                if (input.disabled) {
                    return false;
                }

                if (
                    input.type === 'radio'
                    || input.type === 'checkbox'
                ) {
                    const savedChecked =
                        input.dataset.savedChecked === '1';

                    return input.checked !== savedChecked;
                }

                const savedValue =
                    input.dataset.savedValue ?? '';

                return input.value !== savedValue;
            });
        }

        navigationLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!hasUnsavedPronoChanges()) {
                    return;
                }

                event.preventDefault();

                pendingNavigation = {
                    url: link.href,
                    name: link.dataset.navigationName || 'la journée choisie',
                };

                if (navigationDestination) {
                    navigationDestination.textContent =
                        pendingNavigation.name;
                }

                navigationModalOpenButton?.click();
            });
        });

        continueWithoutSavingButton?.addEventListener(
            'click',
            function () {
                if (!pendingNavigation) {
                    return;
                }

                const targetUrl = pendingNavigation.url;

                removeStoredNavigation();
                pendingNavigation = null;

                window.location.assign(targetUrl);
            }
        );

        saveAndContinueButton?.addEventListener(
            'click',
            function () {
                if (!pendingNavigation || !pronoEntryForm) {
                    return;
                }

                if (!pronoEntryForm.checkValidity()) {
                    navigationModalDismissButton?.click();
                    pronoEntryForm.reportValidity();

                    return;
                }

                navigationToStoreOnSubmit = {
                    url: pendingNavigation.url,
                    name: pendingNavigation.name,
                    submittedAt: Date.now(),
                };

                navigationModalDismissButton?.click();

                pronoEntryForm.requestSubmit();

                window.setTimeout(function () {
                    navigationToStoreOnSubmit = null;
                }, 0);
            }
        );

        pronoEntryForm?.addEventListener('submit', function () {
            if (!navigationToStoreOnSubmit) {
                removeStoredNavigation();

                return;
            }

            storeNavigationAfterSubmit(
                navigationToStoreOnSubmit
            );
        });

        navigationModalElement?.addEventListener(
            'hidden.bs.modal',
            function () {
                pendingNavigation = null;
            }
        );

        triesInputs.forEach(function (input, index) {
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Tab') {
                    return;
                }

                const nextIndex = event.shiftKey
                    ? index - 1
                    : index + 1;

                const nextInput = triesInputs[nextIndex];

                if (!nextInput) {
                    return;
                }

                event.preventDefault();
                nextInput.focus();
                nextInput.select();
            });
        });

        document
            .querySelectorAll('.bonus-choice-label')
            .forEach(function (label) {
                label.addEventListener('pointerdown', function () {
                    const input = document.getElementById(
                        label.getAttribute('for')
                    );

                    if (!input || input.disabled) {
                        return;
                    }

                    input.dataset.wasChecked = input.checked
                        ? '1'
                        : '0';
                });
            });

        document
            .querySelectorAll('.bonus-choice-input')
            .forEach(function (input) {
                input.addEventListener('pointerdown', function () {
                    if (input.disabled) {
                        return;
                    }

                    input.dataset.wasChecked = input.checked
                        ? '1'
                        : '0';
                });

                input.addEventListener('keydown', function (event) {
                    if (input.disabled) {
                        return;
                    }

                    if (event.key === ' ' || event.key === 'Enter') {
                        input.dataset.wasChecked = input.checked
                            ? '1'
                            : '0';
                    }
                });

                input.addEventListener('click', function (event) {
                    if (input.disabled) {
                        return;
                    }

                    if (input.dataset.wasChecked === '1') {
                        event.preventDefault();

                        window.setTimeout(function () {
                            input.checked = false;
                            input.dataset.wasChecked = '0';
                        }, 0);
                    }
                });
            });
    });
</script>
@endpush
