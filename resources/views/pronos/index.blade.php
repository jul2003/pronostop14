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

    <div class="d-flex flex-wrap gap-2">
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

@if($previousJournee || $nextJournee)
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            @if($previousJournee)
                <a href="{{ route('pronos.show', [$season, $previousJournee]) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4"
                   title="{{ $previousJournee->name }}">
                    ← Journée précédente : {{ $previousJournee->name }}
                </a>
            @endif
        </div>

        <div class="ms-auto">
            @if($nextJournee)
                <a href="{{ route('pronos.show', [$season, $nextJournee]) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4"
                   title="{{ $nextJournee->name }}">
                    Journée suivante : {{ $nextJournee->name }} →
                </a>
            @endif
        </div>
    </div>
@endif

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

@if($matches->isEmpty())
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucun match disponible pour cette journée.
        </div>
    </div>
@else
    <form method="POST"
          action="{{ route('pronos.store', [$season, $journee]) }}"
          autocomplete="off">
        @csrf

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
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

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach($journee->resultOptionShortLabels() as $value => $label)
                                            <input type="radio"
                                                   id="predicted_result_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_result]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
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

                                <td class="text-center">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="pronos[{{ $match->id }}][predicted_tries]"
                                           value="{{ $triesValue }}"
                                           class="form-control form-control-sm prono-tries-input mx-auto"
                                           autocomplete="off"
                                           autocorrect="off"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           @disabled($matchIsLocked)
                                           @if(! $matchIsLocked) required @endif>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group bonus-choice-group">
                                        @foreach(['o' => 'o', '-' => '-', 'd' => 'd'] as $value => $label)
                                            <input type="radio"
                                                   id="predicted_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_home_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input bonus-choice-input"
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

                                <td class="text-center">
                                    <div class="prono-choice-group bonus-choice-group">
                                        @foreach(['o' => 'o', '-' => '-', 'd' => 'd'] as $value => $label)
                                            <input type="radio"
                                                   id="predicted_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_away_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input bonus-choice-input"
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

                                <td class="text-center prono-action-cell">
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

        @if($hasOpenMatches)
            <button type="submit"
                    class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
                Enregistrer mes pronostics
            </button>
        @endif
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

@endsection

@push('styles')
<style>
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

    .prono-delete-modal .modal-content {
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
        document.querySelectorAll('.bonus-choice-label').forEach(function (label) {
            label.addEventListener('pointerdown', function () {
                const input = document.getElementById(label.getAttribute('for'));

                if (!input || input.disabled) {
                    return;
                }

                input.dataset.wasChecked = input.checked ? '1' : '0';
            });
        });

        document.querySelectorAll('.bonus-choice-input').forEach(function (input) {
            input.addEventListener('pointerdown', function () {
                if (input.disabled) {
                    return;
                }

                input.dataset.wasChecked = input.checked ? '1' : '0';
            });

            input.addEventListener('keydown', function (event) {
                if (input.disabled) {
                    return;
                }

                if (event.key === ' ' || event.key === 'Enter') {
                    input.dataset.wasChecked = input.checked ? '1' : '0';
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
