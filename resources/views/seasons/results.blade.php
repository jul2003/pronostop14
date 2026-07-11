@extends('layouts.pronos')

@section('content')

@php
    $playerCount = max(1, $players->count());

    $preseasonQuestionTotals = [];
    $preseasonBonusTotals = [];
    $preseasonTotals = [];
    $visibleJourneeTotals = [];
    $visibleGrandTotals = [];
    $journeeTotalsByJournee = [];

    foreach ($players as $player) {
        $preseasonQuestionTotals[$player->id] = 0;
        $preseasonBonusTotals[$player->id] = 0;
        $preseasonTotals[$player->id] = 0;
        $visibleJourneeTotals[$player->id] = 0;
        $visibleGrandTotals[$player->id] = 0;
    }

    if ($preseasonIsVisible) {
        foreach ($preseasonQuestions as $question) {
            foreach ($players as $player) {
                $prediction = $question->predictions->firstWhere('user_id', $player->id);
                $preseasonQuestionTotals[$player->id] += (int) ($prediction?->points ?? 0);
            }
        }

        foreach ($preseasonBonusRules as $bonusRule) {
            $scores = $preseasonBonusScores->get($bonusRule->id, collect());

            foreach ($players as $player) {
                $score = $scores->firstWhere('user_id', $player->id);
                $preseasonBonusTotals[$player->id] += (int) ($score?->points ?? 0);
            }
        }

        foreach ($players as $player) {
            $preseasonTotals[$player->id] = $preseasonQuestionTotals[$player->id] + $preseasonBonusTotals[$player->id];
        }
    }

    foreach ($journees as $journee) {
        $journeeTotalsByJournee[$journee->id] = [];

        foreach ($players as $player) {
            $total = $journee->matches->sum(function ($match) use ($player) {
                return (int) ($match->pronos->firstWhere('user_id', $player->id)?->points ?? 0);
            });

            $journeeTotalsByJournee[$journee->id][$player->id] = $total;
            $visibleJourneeTotals[$player->id] += $total;
        }
    }

    foreach ($players as $player) {
        $visibleGrandTotals[$player->id] = $preseasonTotals[$player->id] + $visibleJourneeTotals[$player->id];
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

    $playerLabel = fn ($player) => $player->nickname ?? $player->name;

    $matchResultLabel = function ($journee, $match) {
        if (! $match->actual_result) {
            return null;
        }

        return $journee->resultOptionShortLabel($match->actual_result)
            .' / '.($match->actual_tries ?? '-')
            .' / '.($match->actual_home_bonus ?: '-')
            .' / '.($match->actual_away_bonus ?: '-');
    };

    $pronoLabel = function ($prono) {
        if (! $prono) {
            return '-';
        }

        return ($prono->predicted_result ?: '-')
            .' / '.($prono->predicted_tries ?? '-')
            .' / '.($prono->predicted_home_bonus ?: '-')
            .' / '.($prono->predicted_away_bonus ?: '-');
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
            Les blocs apparaissent uniquement quand les pronostics sont clôturés.
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
@else
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="rugby-card p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h3 class="h5 fw-bold mb-1">
                            Sommaire
                        </h3>

                        <p class="text-muted mb-0">
                            Accès rapide aux blocs visibles.
                        </p>
                    </div>

                    <span class="badge rounded-pill text-bg-primary px-3 py-2">
                        {{ $players->count() }} joueur(s)
                    </span>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if($preseasonIsVisible)
                        <a href="#preseason-results"
                           class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                            Avant-saison
                        </a>
                    @endif

                    @foreach($journees as $journee)
                        <a href="#journee-results-{{ $journee->id }}"
                           class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                            {{ $journee->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="rugby-card p-4 h-100">
                <h3 class="h6 fw-bold mb-3">
                    Légende
                </h3>

                <div class="d-grid gap-2 small">
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-box cell-ok-dark"></span>
                        <span>Réponse ou résultat principal correct</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-box cell-ok-light"></span>
                        <span>Points obtenus</span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-box cell-ko"></span>
                        <span>Réponse ou résultat principal incorrect</span>
                    </div>

                    <div class="text-muted mt-2">
                        Format match : résultat / essais / bonus domicile / bonus extérieur.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rugby-card p-0 overflow-hidden mb-4">
        <div class="p-4 border-bottom bg-light">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Synthèse des points visibles
                    </h3>

                    <p class="text-muted mb-0">
                        Total calculé uniquement sur les blocs actuellement visibles sur cette page.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-dark px-3 py-2">
                    {{ ($preseasonIsVisible ? 1 : 0) + $journees->count() }} bloc(s)
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 summary-table">
                <thead class="table-light">
                    <tr>
                        <th>Joueur</th>
                        <th class="text-center">Avant-saison</th>
                        <th class="text-center">Journées</th>
                        <th class="text-center">Total visible</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($players as $player)
                        <tr>
                            <td class="fw-bold">
                                <span class="player-dot"
                                      style="background: {{ $player->color ?? '#06142f' }}"></span>
                                {{ $playerLabel($player) }}
                            </td>

                            <td class="text-center">
                                {{ $preseasonTotals[$player->id] ?? 0 }}
                            </td>

                            <td class="text-center">
                                {{ $visibleJourneeTotals[$player->id] ?? 0 }}
                            </td>

                            <td class="text-center fw-bold">
                                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                                    {{ $visibleGrandTotals[$player->id] ?? 0 }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($preseasonIsVisible)
    <section id="preseason-results" class="results-section mb-4">
        <div class="rugby-card p-0 overflow-hidden">
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

                    <div class="text-end">
                        <span class="badge rounded-pill text-bg-success px-3 py-2">
                            Résultats visibles
                        </span>

                        <div class="text-muted small mt-2">
                            {{ $preseasonQuestions->count() }} question(s)
                        </div>
                    </div>
                </div>
            </div>

            @if($preseasonQuestions->isEmpty())
                <div class="p-4 text-muted">
                    Aucune question avant-saison disponible.
                </div>
            @else
                <div class="table-responsive results-scroll">
                    <table class="table table-bordered align-middle mb-0 results-grid">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle sticky-main bg-light">
                                    Question
                                </th>

                                <th rowspan="2" class="align-middle text-center sticky-result bg-light">
                                    Résultat
                                </th>

                                @foreach($players as $player)
                                    <th colspan="2" class="text-center player-heading">
                                        <span class="player-dot"
                                              style="background: {{ $player->color ?? '#06142f' }}"></span>
                                        {{ $playerLabel($player) }}
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
                                    <td class="fw-bold sticky-main bg-white">
                                        <div>
                                            {{ $question->label }}
                                        </div>

                                        <div class="text-muted small">
                                            {{ $question->points }} point(s)
                                        </div>
                                    </td>

                                    <td class="text-center fw-bold sticky-result bg-white">
                                        {{ $officialAnswerLabel($question) }}
                                    </td>

                                    @foreach($players as $player)
                                        @php
                                            $prediction = $question->predictions->firstWhere('user_id', $player->id);
                                            $predictionPoints = (int) ($prediction?->points ?? 0);
                                        @endphp

                                        <td class="text-center {{ $prediction?->is_correct ? 'cell-ok-dark' : ($prediction && $prediction->is_correct === false ? 'cell-ko' : '') }}">
                                            {{ $answerLabel($question, $prediction) }}
                                        </td>

                                        <td class="text-center fw-bold {{ $predictionPoints > 0 ? 'cell-ok-light' : '' }}">
                                            {{ $prediction?->points ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            @foreach($preseasonBonusRules as $bonusRule)
                                @php
                                    $scores = $preseasonBonusScores->get($bonusRule->id, collect());
                                @endphp

                                <tr class="bonus-row">
                                    <td class="fw-bold sticky-main bonus-row">
                                        Bonus — {{ $bonusRule->label }}
                                    </td>

                                    <td class="text-center sticky-result bonus-row">
                                        {{ $bonusRule->points }} point(s)
                                    </td>

                                    @foreach($players as $player)
                                        @php
                                            $score = $scores->firstWhere('user_id', $player->id);
                                            $scorePoints = (int) ($score?->points ?? 0);
                                        @endphp

                                        <td class="text-center">
                                            {{ $score?->is_awarded ? 'Oui' : '-' }}
                                        </td>

                                        <td class="text-center fw-bold {{ $scorePoints > 0 ? 'cell-ok-light' : '' }}">
                                            {{ $score?->points ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <tr class="table-dark">
                                <td colspan="2" class="fw-bold sticky-total table-dark">
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
    </section>
@else
    <div class="alert alert-info">
        La partie avant-saison sera visible ici quand les pronostics avant-saison seront clôturés pour ton compte.
    </div>
@endif

@foreach($journees as $journee)
    <section id="journee-results-{{ $journee->id }}" class="results-section mb-4">
        <div class="rugby-card p-0 overflow-hidden">
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
                <div class="table-responsive results-scroll">
                    <table class="table table-bordered align-middle mb-0 results-grid">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle sticky-main bg-light">
                                    Match
                                </th>

                                <th rowspan="2" class="align-middle text-center sticky-result bg-light">
                                    Résultat
                                </th>

                                @foreach($players as $player)
                                    <th colspan="2" class="text-center player-heading">
                                        <span class="player-dot"
                                              style="background: {{ $player->color ?? '#06142f' }}"></span>
                                        {{ $playerLabel($player) }}
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
                                @php
                                    $officialResult = $matchResultLabel($journee, $match);
                                @endphp

                                <tr>
                                    <td class="fw-bold sticky-main bg-white">
                                        <div>
                                            {{ $match->homeClub->short_name ?? $match->homeClub->name }}
                                            -
                                            {{ $match->awayClub->short_name ?? $match->awayClub->name }}
                                        </div>

                                        <div class="text-muted small">
                                            Match {{ $match->position }}
                                        </div>
                                    </td>

                                    <td class="text-center fw-bold sticky-result bg-white">
                                        @if($officialResult)
                                            <span class="badge rounded-pill text-bg-dark">
                                                {{ $officialResult }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                Non saisi
                                            </span>
                                        @endif
                                    </td>

                                    @foreach($players as $player)
                                        @php
                                            $prono = $match->pronos->firstWhere('user_id', $player->id);
                                            $pronoPoints = (int) ($prono?->points ?? 0);
                                            $resultIsCorrect = $prono && $match->actual_result && $prono->predicted_result === $match->actual_result;
                                            $resultIsWrong = $prono && $match->actual_result && $prono->predicted_result !== $match->actual_result;
                                        @endphp

                                        <td class="text-center {{ $resultIsCorrect ? 'cell-ok-dark' : ($resultIsWrong ? 'cell-ko' : '') }}">
                                            {{ $pronoLabel($prono) }}
                                        </td>

                                        <td class="text-center fw-bold {{ $pronoPoints > 0 ? 'cell-ok-light' : '' }}">
                                            {{ $prono?->points ?? '-' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach

                            <tr class="table-dark">
                                <td colspan="2" class="fw-bold sticky-total table-dark">
                                    Total {{ $journee->name }}
                                </td>

                                @foreach($players as $player)
                                    <td class="text-center">
                                        -
                                    </td>

                                    <td class="text-center fw-bold">
                                        {{ $journeeTotalsByJournee[$journee->id][$player->id] ?? 0 }}
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
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
    .results-section {
        scroll-margin-top: 1.5rem;
    }

    .results-scroll {
        max-height: 70vh;
    }

    .results-grid {
        min-width: {{ 430 + ($playerCount * 170) }}px;
        font-size: 0.92rem;
    }

    .results-grid th,
    .results-grid td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .sticky-main {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 260px;
        max-width: 260px;
        box-shadow: 8px 0 10px rgba(0, 0, 0, 0.04);
    }

    .sticky-result {
        position: sticky;
        left: 260px;
        z-index: 4;
        min-width: 170px;
        max-width: 170px;
        box-shadow: 8px 0 10px rgba(0, 0, 0, 0.04);
    }

    .sticky-total {
        position: sticky;
        left: 0;
        z-index: 5;
        min-width: 430px;
        max-width: 430px;
    }

    thead .sticky-main,
    thead .sticky-result {
        z-index: 6;
    }

    .player-heading {
        min-width: 170px;
    }

    .summary-table th,
    .summary-table td {
        white-space: nowrap;
    }

    .player-dot {
        display: inline-block;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 999px;
        margin-right: 0.35rem;
        vertical-align: middle;
        border: 1px solid rgba(0, 0, 0, 0.15);
    }

    .legend-box {
        display: inline-block;
        width: 1.5rem;
        height: 1.2rem;
        border-radius: 0.35rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        flex: 0 0 auto;
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

    .bonus-row {
        background: #fff3cd !important;
    }

    @media (max-width: 767.98px) {
        .sticky-main {
            min-width: 220px;
            max-width: 220px;
        }

        .sticky-result {
            left: 220px;
            min-width: 145px;
            max-width: 145px;
        }

        .sticky-total {
            min-width: 365px;
            max-width: 365px;
        }

        .results-grid {
            min-width: {{ 365 + ($playerCount * 160) }}px;
            font-size: 0.85rem;
        }

        .player-heading {
            min-width: 160px;
        }
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
