@extends('layouts.pronos')

@section('content')

@php
    $previousJournee = $previousJournee ?? null;
    $nextJournee = $nextJournee ?? null;

    $fromPendingResults = request('from') === 'pending-results';

    $backUrl = $fromPendingResults
        ? route('admin.pending-results.index')
        : route('admin.seasons.journees', $season);

    $backLabel = $fromPendingResults
        ? 'Retour aux résultats à saisir'
        : 'Retour aux journées';

    $currentResultsRouteParameters = $fromPendingResults
        ? [$season, $journee, 'from' => 'pending-results']
        : [$season, $journee];

    $journeeResultsRouteParameters = fn ($targetJournee) => $fromPendingResults
        ? [$season, $targetJournee, 'from' => 'pending-results']
        : [$season, $targetJournee];
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
        <a href="{{ $backUrl }}"
           class="text-decoration-none fw-bold">
            ← {{ $backLabel }}
        </a>

        <div class="mt-3 text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Résultats — {{ $journee->name }}
        </h2>

        <p class="text-muted mb-0">
            @if($season->is_locked)
                {{ $season->name }} — saison verrouillée, résultats consultables uniquement.
            @else
                {{ $season->name }}
            @endif

            @if($journee->first_match_at)
                · premier match : {{ $journee->first_match_at->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
           class="btn btn-outline-primary rounded-pill fw-bold">
            Matchs
        </a>
    </div>
</div>

@if($previousJournee || $nextJournee)
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            @if($previousJournee)
                <a href="{{ route('admin.seasons.journees.results', $journeeResultsRouteParameters($previousJournee)) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4"
                   title="{{ $previousJournee->name }}">
                    ← Journée précédente : {{ $previousJournee->name }}
                </a>
            @endif
        </div>

        <div class="ms-auto">
            @if($nextJournee)
                <a href="{{ route('admin.seasons.journees.results', $journeeResultsRouteParameters($nextJournee)) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4"
                   title="{{ $nextJournee->name }}">
                    Journée suivante : {{ $nextJournee->name }} →
                </a>
            @endif
        </div>
    </div>
@endif

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les résultats de cette saison ne peuvent plus être modifiés.
            Pour les corriger, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if($matches->isEmpty())
    <div class="alert alert-info">
        Aucun match disponible pour cette journée.
    </div>
@else
    <form method="POST"
          action="{{ route('admin.seasons.journees.results.store', $currentResultsRouteParameters) }}"
          autocomplete="off">
        @csrf

        @if($fromPendingResults)
            <input type="hidden"
                   name="from"
                   value="pending-results">
        @endif

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 prono-table admin-results-table">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center">Résultat</th>
                            <th class="text-center">Essais</th>
                            <th class="text-center">Bonus dom.</th>
                            <th class="text-center">Bonus ext.</th>
                            <th class="text-center">
                                Date limite prono exceptionnelle
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matches as $match)
                            @php
                                $exceptionDeadline = $match
                                    ->predictionDeadlineException
                                    ?->prediction_deadline;

                                $hasResultEntry = filled($match->actual_result)
                                    || filled($match->actual_tries)
                                    || filled($match->actual_home_bonus)
                                    || filled($match->actual_away_bonus);
                            @endphp

                            <tr>
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
                                </td>

                                <td class="text-center result-choice-cell">
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <div class="prono-choice-group">
                                            @foreach($journee->resultOptionShortLabels() as $value => $label)
                                                <input type="radio"
                                                       id="actual_result_{{ $match->id }}_{{ $value }}"
                                                       name="matches[{{ $match->id }}][actual_result]"
                                                       value="{{ $value }}"
                                                       class="prono-choice-input js-actual-result-input"
                                                       data-match-id="{{ $match->id }}"
                                                       @checked($match->actual_result === $value)
                                                       @disabled($season->is_locked)>

                                                <label for="actual_result_{{ $match->id }}_{{ $value }}"
                                                       class="prono-choice-label"
                                                       title="{{ $journee->resultOptionLabel($value) }}">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>

                                        @unless($season->is_locked)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-circle clear-result-icon-button js-clear-result-button {{ $hasResultEntry ? '' : 'd-none' }}"
                                                    data-match-id="{{ $match->id }}"
                                                    title="Effacer le résultat"
                                                    aria-label="Effacer le résultat de {{ $match->homeClub->name }} contre {{ $match->awayClub->name }}"
                                                    aria-hidden="{{ $hasResultEntry ? 'false' : 'true' }}">
                                                ×
                                            </button>
                                        @endunless
                                    </div>
                                </td>

                                <td class="text-center">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="matches[{{ $match->id }}][actual_tries]"
                                           value="{{ $match->actual_tries }}"
                                           class="form-control form-control-sm prono-tries-input admin-tries-input js-actual-tries-input mx-auto"
                                           data-match-id="{{ $match->id }}"
                                           autocomplete="off"
                                           autocorrect="off"
                                           autocapitalize="off"
                                           spellcheck="false"
                                           @disabled($season->is_locked)>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach($journee->bonusOptionShortLabels() as $value => $label)
                                            <input type="radio"
                                                   id="actual_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="matches[{{ $match->id }}][actual_home_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input js-actual-home-bonus-input"
                                                   data-match-id="{{ $match->id }}"
                                                   @checked($match->actual_home_bonus === $value)
                                                   @disabled($season->is_locked)>

                                            <label for="actual_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach($journee->bonusOptionShortLabels() as $value => $label)
                                            <input type="radio"
                                                   id="actual_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="matches[{{ $match->id }}][actual_away_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input js-actual-away-bonus-input"
                                                   data-match-id="{{ $match->id }}"
                                                   @checked($match->actual_away_bonus === $value)
                                                   @disabled($season->is_locked)>

                                            <label for="actual_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="exception-deadline-cell">
                                    <div class="input-group input-group-sm">
                                        <input type="datetime-local"
                                               name="deadline_exceptions[{{ $match->id }}][prediction_deadline]"
                                               value="{{ $exceptionDeadline ? $exceptionDeadline->format('Y-m-d\TH:i') : '' }}"
                                               class="form-control exception-deadline-input js-exception-deadline-input"
                                               data-match-id="{{ $match->id }}"
                                               tabindex="-1"
                                               @disabled($season->is_locked)>

                                        @unless($season->is_locked)
                                            <button type="button"
                                                    class="btn btn-outline-secondary js-clear-exception-deadline-button"
                                                    data-match-id="{{ $match->id }}"
                                                    title="Effacer la date limite exceptionnelle"
                                                    aria-label="Effacer la date limite exceptionnelle">
                                                ×
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @unless($season->is_locked)
            <div class="mt-4">
                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer les résultats et exceptions
                </button>
            </div>
        @endunless
    </form>
@endif

@endsection

@push('styles')
<style>
    .admin-results-table th,
    .admin-results-table td {
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
    }

    .admin-results-table .match-cell {
        min-width: 430px;
    }

    .admin-results-table .match-home span,
    .admin-results-table .match-away span {
        white-space: nowrap;
    }

    .admin-results-table .match-line {
        grid-template-columns:
            minmax(170px, 1fr)
            24px
            minmax(170px, 1fr);
    }

    .result-choice-cell {
        min-width: 190px;
    }

    .clear-result-icon-button {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 1.75rem;
        height: 1.75rem;
        padding: 0;
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
    }

    .exception-deadline-cell {
        min-width: 250px;
        width: 250px;
    }

    .exception-deadline-input {
        min-width: 190px;
    }

    .js-clear-exception-deadline-button {
        min-width: 2.5rem;
        font-size: 1.15rem;
        line-height: 1;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const triesInputs = Array.from(
            document.querySelectorAll('.admin-tries-input')
        ).filter(function (input) {
            return !input.disabled;
        });

        function matchHasResultEntry(matchId) {
            const resultInput = document.querySelector(
                '.js-actual-result-input[data-match-id="'
                    + matchId
                    + '"]:checked'
            );

            const homeBonusInput = document.querySelector(
                '.js-actual-home-bonus-input[data-match-id="'
                    + matchId
                    + '"]:checked'
            );

            const awayBonusInput = document.querySelector(
                '.js-actual-away-bonus-input[data-match-id="'
                    + matchId
                    + '"]:checked'
            );

            const triesInput = document.querySelector(
                '.js-actual-tries-input[data-match-id="'
                    + matchId
                    + '"]'
            );

            const hasTries = triesInput
                && triesInput.value.trim() !== '';

            return Boolean(
                resultInput
                || homeBonusInput
                || awayBonusInput
                || hasTries
            );
        }

        function updateClearResultButton(matchId) {
            const button = document.querySelector(
                '.js-clear-result-button[data-match-id="'
                    + matchId
                    + '"]'
            );

            if (!button) {
                return;
            }

            const hasResultEntry = matchHasResultEntry(matchId);

            button.classList.toggle('d-none', !hasResultEntry);
            button.setAttribute(
                'aria-hidden',
                hasResultEntry ? 'false' : 'true'
            );
        }

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
            .querySelectorAll(
                '.js-actual-result-input, '
                + '.js-actual-tries-input, '
                + '.js-actual-home-bonus-input, '
                + '.js-actual-away-bonus-input'
            )
            .forEach(function (input) {
                input.addEventListener('input', function () {
                    updateClearResultButton(input.dataset.matchId);
                });

                input.addEventListener('change', function () {
                    updateClearResultButton(input.dataset.matchId);
                });
            });

        document
            .querySelectorAll('.js-clear-result-button')
            .forEach(function (button) {
                updateClearResultButton(button.dataset.matchId);

                button.addEventListener('click', function () {
                    const matchId = button.dataset.matchId;

                    document.querySelectorAll(
                        '.js-actual-result-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    document.querySelectorAll(
                        '.js-actual-home-bonus-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    document.querySelectorAll(
                        '.js-actual-away-bonus-input[data-match-id="'
                            + matchId
                            + '"]'
                    ).forEach(function (input) {
                        input.checked = false;
                    });

                    const triesInput = document.querySelector(
                        '.js-actual-tries-input[data-match-id="'
                            + matchId
                            + '"]'
                    );

                    if (triesInput && !triesInput.disabled) {
                        triesInput.value = '';
                    }

                    updateClearResultButton(matchId);
                });
            });

        document
            .querySelectorAll('.js-clear-exception-deadline-button')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    const matchId = button.dataset.matchId;

                    const input = document.querySelector(
                        '.js-exception-deadline-input[data-match-id="'
                            + matchId
                            + '"]'
                    );

                    if (!input || input.disabled) {
                        return;
                    }

                    input.value = '';
                    input.focus();
                });
            });
    });
</script>
@endpush
