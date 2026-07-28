@extends('layouts.pronos')

@section('content')

@php
    $hasOpenMatches = $matches->contains(fn ($match) => ! $match->isPredictionLocked());
    $predictionNotice = $predictionNotice ?? null;
    $predictionWarning = session('prediction_warning');
    $rankingIsAvailable = $rankingIsAvailable ?? false;
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

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@elseif($predictionWarning)
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
                                        @foreach(['o' => 'O', '-' => '-', 'd' => 'D'] as $value => $label)
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
                                        @foreach(['o' => 'O', '-' => '-', 'd' => 'D'] as $value => $label)
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($hasOpenMatches)
            <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
                Enregistrer mes pronostics
            </button>
        @endif
    </form>
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
