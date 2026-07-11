@extends('layouts.pronos')

@section('content')

@php
    $playerCount = max(1, $players->count());

    $preseasonQuestionTotals = [];
    $preseasonBonusTotals = [];
    $preseasonTotals = [];

    foreach ($players as $player) {
        $preseasonQuestionTotals[$player->id] = 0;
        $preseasonBonusTotals[$player->id] = 0;
        $preseasonTotals[$player->id] = 0;
    }

    if($preseasonIsVisible) {
        foreach($preseasonQuestions as $question) {
            foreach($players as $player) {
                $prediction = $question->predictions->firstWhere('user_id', $player->id);
                $preseasonQuestionTotals[$player->id] += (int) ($prediction?->points ?? 0);
            }
        }

        foreach($preseasonBonusRules as $bonusRule) {
            $scores = $preseasonBonusScores->get($bonusRule->id, collect());

            foreach($players as $player) {
                $score = $scores->firstWhere('user_id', $player->id);
                $preseasonBonusTotals[$player->id] += (int) ($score?->points ?? 0);
            }
        }

        foreach($players as $player) {
            $preseasonTotals[$player->id] = $preseasonQuestionTotals[$player->id] + $preseasonBonusTotals[$player->id];
        }
    }

    $answerLabel = function ($question, $prediction = null) {
        if (! $prediction) {
            return '-';
        }

        if ($question->answer_type === 'free_text') {
            return $prediction->text_answer ?: '-';
        }

        return $prediction->club?->short_name
            ?? $prediction->club?->name
            ?? '-';
    };

    $officialAnswerLabel = function ($question) {
        if ($question->answer_type === 'free_text') {
            return $question->result_text_answer ?: '-';
        }

        return $question->resultClub?->short_name
            ?? $question->resultClub?->name
            ?? '-';
    };
@endphp

<div id="page-top" class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Résultats
        </div>

        <h2 class="fw-bold mb-1">
            Saison {{ $season->name }}
        </h2>

        <p class="text-muted mb-0">
            Feuille de suivi des résultats, pronostics et points par joueur.
            Les journées s’affichent uniquement quand les pronostics sont clôturés.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('pronos.index') }}"
           class="btn btn-outline-secondary rounded-pill fw-bold px-4">
            ← Retour aux pronos
        </a>

        <a href="{{ route('rankings.general', $season) }}"
           class="btn btn-warning rounded-pill fw-bold px-4">
            Classement général
        </a>
    </div>
</div>

@if(! $preseasonIsVisible && $journees->isEmpty())
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucun résultat n’est encore visible pour cette saison.
            Les résultats apparaîtront ici quand les pronostics seront clôturés.
        </div>
    </div>
@endif

@if($preseasonIsVisible)
    <div class="rugby-card p-0 overflow-hidden mb-4">
        <div class="p-4 border-bottom bg-light">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-uppercase text-primary fw-bold small">
                        Avant-saison
                    </div>

                    <h3 class="h5 fw-bold mb-1">
                        Pronostics avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        @if($preseasonDeadline)
                            Pronostics clôturés depuis le {{ $preseasonDeadline->format('d/m/Y H:i') }}.
                        @else
                            Pronostics clôturés.
                        @endif
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-success px-3 py-2">
                    Résultats visibles
                </span>
            </div>
        </div>

        @if($preseasonQuestions->isEmpty())
            <div class="p-4 text-muted">
                Aucune question avant-saison disponible.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 results-grid">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle sticky-col bg-light">
                                Question
                            </th>

                            <th rowspan="2" class="align-middle text-center bg-light">
                                Résultat
                            </th>

                            @foreach($players as $player)
                                <th colspan="2" class="text-center player-heading">
                                    {{ $player->nickname ?? $player->name }}
                                </th>
                            @endforeach
                        </tr>

                        <tr>
                            @foreach($players as $player)
                                <th class="text-center small">Réponse</th>
                                <th class="text-center small">Pts</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($preseasonQuestions as $question)
                            <tr>
                                <td class="fw-bold sticky-col bg-white">
                                    <div>
                                        {{ $question->label }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ $question->points }} point(s)
                                    </div>
                                </td>

                                <td class="text-center fw-bold">
                                    {{ $officialAnswerLabel($question) }}
                                </td>

                                @foreach($players as $player)
                                    @php
                                        $prediction = $question->predictions->firstWhere('user_id', $player->id);
                                    @endphp

                                    <td class="text-center {{ $prediction?->is_correct ? 'cell-ok-dark' : ($prediction && $prediction->is_correct === false ? 'cell-ko' : '') }}">
                                        {{ $answerLabel($question, $prediction) }}
                                    </td>

                                    <td class="text-center fw-bold {{ ((int) ($prediction?->points ?? 0)) > 0 ? 'cell-ok-light' : '' }}">
                                        {{ $prediction?->points ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        @foreach($preseasonBonusRules as $bonusRule)
                            @php
                                $scores = $preseasonBonusScores->get($bonusRule->id, collect());
                            @endphp

                            <tr class="table-warning">
                                <td class="fw-bold sticky-col table-warning">
                                    Bonus — {{ $bonusRule->label }}
                                </td>

                                <td class="text-center">
                                    {{ $bonusRule->points }} point(s)
                                </td>

                                @foreach($players as $player)
                                    @php
                                        $score = $scores->firstWhere('user_id', $player->id);
                                    @endphp

                                    <td class="text-center">
                                        {{ $score?->is_awarded ? 'Oui' : '-' }}
                                    </td>

                                    <td class="text-center fw-bold">
                                        {{ $score?->points ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="table-dark">
                            <td colspan="2" class="fw-bold sticky-col table-dark">
                                Total avant-saison
                            </td>

                            @foreach($players as $player)
                                <td class="text-center">
                                    Questions : {{ $preseasonQuestionTotals[$player->id] ?? 0 }}
                                </td>

                                <td class="text-center fw-bold">
                                    {{ $preseasonTotals[$player->id] ?? 0 }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@else
    <div class="alert alert-info">
        La partie avant-saison sera visible ici quand les pronostics avant-saison seront clôturés pour ton compte.
    </div>
@endif

@foreach($journees as $journee)
    <div class="rugby-card p-0 overflow-hidden mb-4">
        <div class="p-4 border-bottom bg-light">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-uppercase text-primary fw-bold small">
                        {{ $journee->type_label }}
                    </div>

                    <h3 class="h5 fw-bold mb-1">
                        {{ $journee->name }}
                    </h3>

                    <p class="text-muted mb-0">
                        @if($journee->prediction_deadline)
                            Pronostics clôturés depuis le {{ $journee->prediction_deadline->format('d/m/Y H:i') }}.
                        @else
                            Date limite non définie.
                        @endif
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('journees.results', [$season, $journee]) }}"
                       class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                        Détail journée
                    </a>

                    <a href="{{ route('rankings.journee', [$season, $journee]) }}"
                       class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                        Classement journée
                    </a>
                </div>
            </div>
        </div>

        @if($journee->matches->isEmpty())
            <div class="p-4 text-muted">
                Aucun match renseigné pour cette journée.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 results-grid">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle sticky-col bg-light">
                                Match
                            </th>

                            <th rowspan="2" class="align-middle text-center bg-light">
                                Résultat
                            </th>

                            @foreach($players as $player)
                                <th colspan="2" class="text-center player-heading">
                                    {{ $player->nickname ?? $player->name }}
                                </th>
                            @endforeach
                        </tr>

                        <tr>
                            @foreach($players as $player)
                                <th class="text-center small">Prono</th>
                                <th class="text-center small">Pts</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($journee->matches->sortBy('position') as $match)
                            <tr>
                                <td class="fw-bold sticky-col bg-white">
                                    <div>
                                        {{ $match->homeClub->short_name ?? $match->homeClub->name }}
                                        -
                                        {{ $match->awayClub->short_name ?? $match->awayClub->name }}
                                    </div>

                                    <div class="text-muted small">
                                        Match {{ $match->position }}
                                    </div>
                                </td>

                                <td class="text-center fw-bold">
                                    @if($match->actual_result)
                                        {{ $journee->resultOptionShortLabel($match->actual_result) }}
                                        /
                                        {{ $match->actual_tries ?? '-' }}
                                        /
                                        {{ $match->actual_home_bonus ?: '-' }}
                                        /
                                        {{ $match->actual_away_bonus ?: '-' }}
                                    @else
                                        -
                                    @endif
                                </td>

                                @foreach($players as $player)
                                    @php
                                        $prono = $match->pronos->firstWhere('user_id', $player->id);
                                        $resultIsCorrect = $prono && $match->actual_result && $prono->predicted_result === $match->actual_result;
                                    @endphp

                                    <td class="text-center {{ $resultIsCorrect ? 'cell-ok-dark' : ($prono && $match->actual_result ? 'cell-ko' : '') }}">
                                        @if($prono)
                                            {{ $prono->predicted_result }}
                                            /
                                            {{ $prono->predicted_tries }}
                                            /
                                            {{ $prono->predicted_home_bonus ?: '-' }}
                                            /
                                            {{ $prono->predicted_away_bonus ?: '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="text-center fw-bold {{ ((int) ($prono?->points ?? 0)) > 0 ? 'cell-ok-light' : '' }}">
                                        {{ $prono?->points ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="table-dark">
                            <td colspan="2" class="fw-bold sticky-col table-dark">
                                Total {{ $journee->name }}
                            </td>

                            @foreach($players as $player)
                                @php
                                    $total = $journee->matches->sum(function ($match) use ($player) {
                                        return $match->pronos
                                            ->firstWhere('user_id', $player->id)
                                            ?->points ?? 0;
                                    });
                                @endphp

                                <td class="text-center">
                                    -
                                </td>

                                <td class="text-center fw-bold">
                                    {{ $total }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endforeach

<button type="button"
        id="backToTopButton"
        class="btn btn-primary rounded-circle shadow position-fixed d-none"
        style="right: 1.25rem; bottom: 1.25rem; z-index: 1050; width: 3rem; height: 3rem;"
        aria-label="Retour en haut"
        title="Retour en haut">
    ↑
</button>

@endsection

@push('styles')
<style>
    .results-grid {
        min-width: {{ 360 + ($playerCount * 180) }}px;
    }

    .results-grid th,
    .results-grid td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 2;
        min-width: 260px;
    }

    .player-heading {
        min-width: 180px;
    }

    .cell-ok-dark {
        background: #198754 !important;
        color: #ffffff !important;
    }

    .cell-ok-light {
        background: #d1e7dd !important;
    }

    .cell-ko {
        background: #f8d7da !important;
    }
</style>
@endpush

@push('scripts')
<script>
    function setupBackToTopButton() {
        const button = document.getElementById('backToTopButton');

        if (!button) {
            return;
        }

        function refreshButtonVisibility() {
            if (window.scrollY > 350) {
                button.classList.remove('d-none');
            } else {
                button.classList.add('d-none');
            }
        }

        button.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', refreshButtonVisibility, {
            passive: true
        });

        refreshButtonVisibility();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupBackToTopButton();
    });
</script>
@endpush
