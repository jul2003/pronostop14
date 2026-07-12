@extends('layouts.pronos')

@section('content')

@php
    $safeColor = function ($value, $fallback = '#06142F') {
        $color = strtoupper((string) $value);

        return preg_match('/^#[0-9A-F]{6}$/', $color) ? $color : $fallback;
    };

    $playerLabel = fn ($player) => $player->nickname ?? $player->name;
    $playerColor = fn ($player) => $safeColor($player->color ?? '#06142F');

    $verticalWord = function (string $word) {
        return collect(str_split($word))
            ->map(fn ($letter) => '<span>'.e($letter).'</span>')
            ->implode('');
    };

    $verticalBonus = function (string $side) use ($verticalWord) {
        return '<span class="vertical-bonus-label">'
            .'<span class="vertical-bonus-word">'.$verticalWord('Bonus').'</span>'
            .'<span class="vertical-bonus-side">'.$verticalWord($side).'</span>'
            .'</span>';
    };

    $journeeSelectionLabel = function ($journee) {
        if ($journee->type === 'regular') {
            return 'J'.$journee->number;
        }

        return match ($journee->type) {
            'top14_playoff' => 'Barrages TOP 14',
            'access_match' => 'Access match',
            'top14_semifinal' => 'Demi-finales TOP 14',
            'prod2_final' => 'Finale PRO D2',
            'top14_final' => 'Finale TOP 14',
            default => $journee->name,
        };
    };

    $journeeDateLabel = function ($journee) {
        if ($journee->starts_at) {
            return $journee->starts_at->format('d/m');
        }

        if ($journee->prediction_deadline) {
            return $journee->prediction_deadline->format('d/m');
        }

        return '';
    };

    $clubLabel = fn ($club) => $club?->name ?? $club?->short_name ?? '';

    $preseasonAnswerLabel = function ($question, $prediction = null) use ($clubLabel) {
        if (! $prediction) {
            return '';
        }

        if ($question->answer_type === 'free_text') {
            return $prediction->text_answer ?: '';
        }

        return $clubLabel($prediction->club);
    };

    $preseasonOfficialLabel = function ($question) use ($clubLabel) {
        if ($question->answer_type === 'free_text') {
            return $question->result_text_answer ?: '';
        }

        return $clubLabel($question->resultClub);
    };

    $resultValue = fn ($value) => $value ? strtolower((string) $value) : '';

    $bonusValue = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        return strtolower((string) $value);
    };

    $statusClass = fn ($status) => match ($status) {
        'good' => 'result-good',
        'bad' => 'result-bad',
        'bonus' => 'result-bonus',
        default => 'result-neutral',
    };

    $totalColumns = 6 + ($players->count() * 9);
@endphp

<div class="results-page"
     style="--result-good-bg: {{ $resultColors['correct'] }};
            --result-bad-bg: {{ $resultColors['wrong'] }};
            --result-bonus-bg: {{ $resultColors['bonus_offset'] }};
            --preseason-bonus-bg: {{ $resultColors['preseason_bonus'] }};">

    <div id="page-top" class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase text-primary fw-bold small">
                Résultats
            </div>

            <h2 class="fw-bold mb-1">
                {{ $selectedSeason->name }}
            </h2>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('bilan.season'))
                <a href="{{ route('bilan.season', $selectedSeason) }}"
                   class="btn btn-outline-primary rounded-pill fw-bold px-4">
                    Bilan
                </a>
            @endif

            <a href="{{ route('rankings.general', $selectedSeason) }}"
               class="btn btn-warning rounded-pill fw-bold px-4">
                Classement général
            </a>
        </div>
    </div>

    <div class="results-control-panel rugby-card p-2 p-lg-3 mb-3">
        <div class="row g-3 align-items-start">
            <div class="col-xl-8">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <h3 class="h6 fw-bold mb-0">
                            Classement saison
                        </h3>

                        <div class="text-muted small">
                            Journées + avant-saison
                        </div>
                    </div>

                    <span class="badge rounded-pill text-bg-dark">
                        {{ $players->count() }} joueur(s)
                    </span>
                </div>

                <div class="table-responsive ranking-scroll">
                    <table class="table table-sm table-hover align-middle compact-ranking-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Joueur</th>
                                <th class="text-center">Journées</th>
                                <th class="text-center">Avant-saison</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($rankingRows as $row)
                                <tr>
                                    <td class="fw-bold ranking-player-cell"
                                        style="color: {{ $playerColor($row['user']) }};">
                                        {{ $playerLabel($row['user']) }}
                                    </td>

                                    <td class="text-center">
                                        {{ $row['journee_points'] }}
                                    </td>

                                    <td class="text-center">
                                        {{ $row['preseason_points'] }}
                                    </td>

                                    <td class="text-center fw-bold">
                                        {{ $row['total_points'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="filters-panel">
                    <div class="mb-2">
                        <label for="seasonSelect" class="form-label fw-bold small mb-1">
                            Saison
                        </label>

                        <select id="seasonSelect" class="form-select form-select-sm">
                            @foreach($seasons as $seasonOption)
                                <option value="{{ route('results.season', $seasonOption) }}"
                                        @selected($seasonOption->id === $selectedSeason->id)>
                                    {{ $seasonOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="journeeSelect" class="form-label fw-bold small mb-1">
                            Journée
                        </label>

                        <select id="journeeSelect" class="form-select form-select-sm">
                            <option value="{{ route('results.season', $selectedSeason) }}"
                                    @selected(! $selectedJournee)>
                                Avant-saison / haut
                            </option>

                            @foreach($journees as $journee)
                                <option value="{{ route('results.journee', [$selectedSeason, $journee]) }}"
                                        @selected($selectedJournee?->id === $journee->id)>
                                    {{ $journeeSelectionLabel($journee) }}
                                    @if(! $journee->isLocked())
                                        — masqué
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive results-table-wrapper" id="resultsTableWrapper">
            <table class="table align-middle mb-0 excel-results-table">
                <thead>
                    <tr class="main-header-row">
                        <th colspan="6" class="left-main-head"></th>

                        @foreach($players as $player)
                            <th colspan="9"
                                class="player-main-head text-center"
                                style="--player-color: {{ $playerColor($player) }};">
                                {{ $playerLabel($player) }}
                            </th>
                        @endforeach
                    </tr>

                    <tr class="sub-header-row">
                        <th class="left-col left-journee sticky-left-journee">Journée</th>
                        <th class="left-col left-date sticky-left-date">Date</th>

                        <th class="left-col left-mini-col sticky-left-rez">
                            <span class="vertical-word">{!! $verticalWord('REZ') !!}</span>
                        </th>

                        <th class="left-col left-mini-col sticky-left-try">
                            <span class="vertical-word">{!! $verticalWord('TRY') !!}</span>
                        </th>

                        <th class="left-col left-bonus-col sticky-left-bonus-dom">
                            {!! $verticalBonus('Dom') !!}
                        </th>

                        <th class="left-col left-bonus-col sticky-left-bonus-ext">
                            {!! $verticalBonus('Ext') !!}
                        </th>

                        @foreach($players as $player)
                            <th class="player-sub-head player-mini-sub-head player-group-start-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                <span class="vertical-word">{!! $verticalWord('REZ') !!}</span>
                            </th>

                            <th class="player-sub-head player-mini-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                <span class="vertical-word">{!! $verticalWord('TRY') !!}</span>
                            </th>

                            <th class="player-sub-head player-bonus-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                {!! $verticalBonus('Dom') !!}
                            </th>

                            <th class="player-sub-head player-bonus-sub-head player-prono-end-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                {!! $verticalBonus('Ext') !!}
                            </th>

                            <th class="player-sub-head player-mini-sub-head player-result-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                <span class="vertical-word">{!! $verticalWord('REZ') !!}</span>
                            </th>

                            <th class="player-sub-head player-mini-sub-head player-result-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                <span class="vertical-word">{!! $verticalWord('TRY') !!}</span>
                            </th>

                            <th class="player-sub-head player-bonus-sub-head player-result-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                {!! $verticalBonus('Dom') !!}
                            </th>

                            <th class="player-sub-head player-bonus-sub-head player-result-sub-head player-result-end-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                {!! $verticalBonus('Ext') !!}
                            </th>

                            <th class="player-sub-head player-p-sub-head"
                                style="--player-color: {{ $playerColor($player) }};">
                                P
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    <tr class="section-spacer-row">
                        <td colspan="{{ $totalColumns }}"></td>
                    </tr>

                    <tr class="section-title-row">
                        <td colspan="6" class="sticky-left-full section-title-cell">
                            PRONOS D'AVANT SAISON
                        </td>

                        @foreach($players as $player)
                            <td colspan="9" class="player-summary-block-cell"></td>
                        @endforeach
                    </tr>

                    @if(! $preseasonIsVisible)
                        <tr>
                            <td colspan="6" class="sticky-left-full fw-bold">
                                Avant-saison non visible
                            </td>

                            @foreach($players as $player)
                                <td colspan="9" class="player-summary-block-cell"></td>
                            @endforeach
                        </tr>
                    @elseif($preseasonQuestions->isEmpty())
                        <tr>
                            <td colspan="6" class="sticky-left-full fw-bold">
                                Aucun prono avant-saison
                            </td>

                            @foreach($players as $player)
                                <td colspan="9" class="player-summary-block-cell"></td>
                            @endforeach
                        </tr>
                    @else
                        @foreach($preseasonQuestions as $question)
                            <tr class="preseason-row">
                                <td colspan="6" class="sticky-left-full preseason-left-combined-cell">
                                    <span class="preseason-left-grid">
                                        <span class="preseason-label-cell">
                                            {{ $question->label }}
                                        </span>

                                        <span class="preseason-result-cell">
                                            {{ $preseasonOfficialLabel($question) }}
                                        </span>
                                    </span>
                                </td>

                                @foreach($players as $player)
                                    @php
                                        $prediction = $question->predictions->firstWhere('user_id', $player->id);
                                        $points = (int) ($prediction?->points ?? 0);
                                        $hasResult = $question->hasOfficialResult();
                                        $hasBonusHighlight = $preseasonBonusQuestionHighlights[$question->id][$player->id] ?? false;
                                    @endphp

                                    <td colspan="8"
                                        class="player-preseason-prono-cell player-group-start-cell {{ $hasBonusHighlight ? 'preseason-bonus-hit' : '' }}"
                                        style="--player-color: {{ $playerColor($player) }};">
                                        {{ $preseasonAnswerLabel($question, $prediction) }}
                                    </td>

                                    <td class="player-points-cell player-preseason-points-cell {{ $hasBonusHighlight ? 'preseason-bonus-hit' : '' }}"
                                        style="--player-color: {{ $playerColor($player) }};">
                                        @if($hasResult)
                                            {{ $points }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="total-row">
                            <td colspan="6" class="sticky-left-full total-label-cell">
                                TOTAL
                            </td>

                            @foreach($players as $player)
                                <td colspan="9"
                                    class="player-summary-block-cell total-player-block-cell"
                                    style="--player-color: {{ $playerColor($player) }};">
                                    {{ $preseasonTotals[$player->id] ?? 0 }}
                                </td>
                            @endforeach
                        </tr>
                    @endif

                    <tr class="section-spacer-row">
                        <td colspan="{{ $totalColumns }}"></td>
                    </tr>

                    @foreach($journees as $journee)
                        <tr id="journee-{{ $journee->slug }}" class="journee-title-row">
                            <td colspan="6" class="sticky-left-full journee-left-combined-cell">
                                <span class="journee-left-grid">
                                    <span class="journee-name-cell">
                                        {{ $journeeSelectionLabel($journee) }}
                                    </span>

                                    <span class="journee-date-cell">
                                        {{ $journeeDateLabel($journee) }}
                                    </span>

                                    <span></span>
                                </span>
                            </td>

                            @foreach($players as $player)
                                @php
                                    $perfectBonus = $journeePerfectBonuses[$journee->id][$player->id] ?? 0;
                                @endphp

                                <td colspan="9"
                                    class="player-summary-block-cell journee-bonus-block-cell"
                                    style="--player-color: {{ $playerColor($player) }};">
                                    @if($journee->isLocked() && $perfectBonus > 0)
                                        +{{ $perfectBonus }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        @if($journee->matches->isEmpty())
                            <tr>
                                <td colspan="6" class="sticky-left-full text-muted">
                                    Aucun match
                                </td>

                                @foreach($players as $player)
                                    <td colspan="9" class="player-summary-block-cell"></td>
                                @endforeach
                            </tr>
                        @else
                            @foreach($journee->matches as $match)
                                <tr class="match-row">
                                    <td class="team-cell team-home-cell sticky-left-journee">
                                        {{ $clubLabel($match->homeClub) }}
                                    </td>

                                    <td class="team-cell team-away-cell sticky-left-date">
                                        {{ $clubLabel($match->awayClub) }}
                                    </td>

                                    <td class="official-value-cell official-mini-cell sticky-left-rez">
                                        {{ $resultValue($match->actual_result) }}
                                    </td>

                                    <td class="official-value-cell official-mini-cell sticky-left-try">
                                        {{ $match->actual_tries ?? '' }}
                                    </td>

                                    <td class="official-value-cell official-bonus-cell sticky-left-bonus-dom">
                                        {{ $bonusValue($match->actual_home_bonus) }}
                                    </td>

                                    <td class="official-value-cell official-bonus-cell sticky-left-bonus-ext">
                                        {{ $bonusValue($match->actual_away_bonus) }}
                                    </td>

                                    @foreach($players as $player)
                                        @php
                                            $prono = $match->pronos->firstWhere('user_id', $player->id);
                                            $breakdown = $matchBreakdowns[$match->id][$player->id] ?? null;
                                        @endphp

                                        @if($journee->isLocked())
                                            <td class="player-prono-cell player-mini-cell player-group-start-cell"
                                                style="--player-color: {{ $playerColor($player) }};">
                                                {{ $resultValue($prono?->predicted_result) }}
                                            </td>

                                            <td class="player-prono-cell player-mini-cell"
                                                style="--player-color: {{ $playerColor($player) }};">
                                                {{ $prono?->predicted_tries ?? '' }}
                                            </td>

                                            <td class="player-prono-cell player-bonus-cell"
                                                style="--player-color: {{ $playerColor($player) }};">
                                                {{ $bonusValue($prono?->predicted_home_bonus) }}
                                            </td>

                                            <td class="player-prono-cell player-bonus-cell player-prono-end-cell"
                                                style="--player-color: {{ $playerColor($player) }};">
                                                {{ $bonusValue($prono?->predicted_away_bonus) }}
                                            </td>

                                            <td class="result-indicator-cell result-mini-cell {{ $statusClass($breakdown['result_status'] ?? 'neutral') }}"></td>
                                            <td class="result-indicator-cell result-mini-cell {{ $statusClass($breakdown['tries_status'] ?? 'neutral') }}"></td>
                                            <td class="result-indicator-cell result-bonus-cell {{ $statusClass($breakdown['home_bonus_status'] ?? 'neutral') }}"></td>
                                            <td class="result-indicator-cell result-bonus-cell player-result-end-cell {{ $statusClass($breakdown['away_bonus_status'] ?? 'neutral') }}"></td>

                                            <td class="player-points-cell"
                                                style="--player-color: {{ $playerColor($player) }};">
                                                @if(($breakdown['match_points'] ?? null) !== null)
                                                    {{ $breakdown['match_points'] }}
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="9" class="player-summary-block-cell"></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif

                        <tr class="total-row">
                            <td colspan="6" class="sticky-left-full total-label-cell">
                                TOTAL
                            </td>

                            @foreach($players as $player)
                                @php
                                    $matchPoints = $journeeMatchPoints[$journee->id][$player->id] ?? 0;
                                    $perfectBonus = $journeePerfectBonuses[$journee->id][$player->id] ?? 0;
                                    $total = $matchPoints + $perfectBonus;
                                @endphp

                                <td colspan="9"
                                    class="player-summary-block-cell total-player-block-cell"
                                    style="--player-color: {{ $playerColor($player) }};">
                                    @if($journee->isLocked())
                                        {{ $total }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <tr class="section-spacer-row">
                            <td colspan="{{ $totalColumns }}"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <button type="button"
            id="backToTopButton"
            class="btn btn-primary rounded-circle shadow position-fixed d-none"
            style="right: 1.25rem; bottom: 1.25rem; z-index: 1050; width: 3rem; height: 3rem;"
            aria-label="Retour en haut"
            title="Retour en haut">
        ↑
    </button>
</div>

@endsection

@push('styles')
<style>
    .results-page {
        --left-journee-width: 128px;
        --left-date-width: 128px;
        --left-mini-width: 30px;
        --left-bonus-width: 44px;
        --left-info-width: 256px;
        --left-result-width: 148px;
        --left-total-width: 404px;
        --left-offset-date: 128px;
        --left-offset-rez: 256px;
        --left-offset-try: 286px;
        --left-offset-bonus-dom: 316px;
        --left-offset-bonus-ext: 360px;
    }

    .results-control-panel {
        border: 2px solid rgba(13, 110, 253, 0.12);
    }

    .ranking-scroll {
        max-height: none;
        overflow-y: visible;
    }

    .compact-ranking-table {
        font-size: 0.78rem;
    }

    .compact-ranking-table th,
    .compact-ranking-table td {
        padding: 0.22rem 0.4rem;
        white-space: nowrap;
    }

    .ranking-player-cell {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .filters-panel {
        border-left: 1px solid rgba(0, 0, 0, 0.08);
        padding-left: 1rem;
    }

    .results-table-wrapper {
        max-height: 78vh;
        overflow: auto;
        border-top: 2px solid #000;
    }

    .excel-results-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.78rem;
        min-width: max-content;
    }

    .excel-results-table th,
    .excel-results-table td {
        border-color: transparent !important;
        padding: 0.12rem 0.22rem;
        white-space: nowrap;
        vertical-align: middle;
        line-height: 1.05;
        background-clip: padding-box;
    }

    .excel-results-table thead tr:first-child th {
        position: sticky;
        top: 0;
        z-index: 40;
        height: 28px;
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
    }

    .excel-results-table thead tr:nth-child(2) th {
        position: sticky;
        top: 28px;
        z-index: 39;
        height: 92px;
        border-bottom: 2px solid #000 !important;
    }

    .left-main-head {
        position: sticky !important;
        left: 0;
        z-index: 70 !important;
        background: #ffffff !important;
        min-width: var(--left-total-width);
        max-width: var(--left-total-width);
        width: var(--left-total-width);
        border-right: 2px solid #000 !important;
    }

    .left-col {
        background: #ffffff !important;
        color: #000000;
        text-align: center;
        font-weight: 800;
    }

    .sticky-left-journee,
    .sticky-left-date,
    .sticky-left-rez,
    .sticky-left-try,
    .sticky-left-bonus-dom,
    .sticky-left-bonus-ext {
        position: sticky !important;
        z-index: 25 !important;
        background: #ffffff !important;
    }

    thead .sticky-left-journee,
    thead .sticky-left-date,
    thead .sticky-left-rez,
    thead .sticky-left-try,
    thead .sticky-left-bonus-dom,
    thead .sticky-left-bonus-ext {
        z-index: 65 !important;
    }

    .sticky-left-journee {
        left: 0;
        min-width: var(--left-journee-width);
        max-width: var(--left-journee-width);
        width: var(--left-journee-width);
    }

    .sticky-left-date {
        left: var(--left-offset-date);
        min-width: var(--left-date-width);
        max-width: var(--left-date-width);
        width: var(--left-date-width);
    }

    .sticky-left-rez {
        left: var(--left-offset-rez);
        min-width: var(--left-mini-width);
        max-width: var(--left-mini-width);
        width: var(--left-mini-width);
    }

    .sticky-left-try {
        left: var(--left-offset-try);
        min-width: var(--left-mini-width);
        max-width: var(--left-mini-width);
        width: var(--left-mini-width);
    }

    .sticky-left-bonus-dom {
        left: var(--left-offset-bonus-dom);
        min-width: var(--left-bonus-width);
        max-width: var(--left-bonus-width);
        width: var(--left-bonus-width);
    }

    .sticky-left-bonus-ext {
        left: var(--left-offset-bonus-ext);
        min-width: var(--left-bonus-width);
        max-width: var(--left-bonus-width);
        width: var(--left-bonus-width);
        border-right: 2px solid #000 !important;
    }

    .sticky-left-full {
        position: sticky !important;
        left: 0;
        z-index: 26 !important;
        min-width: var(--left-total-width);
        max-width: var(--left-total-width);
        width: var(--left-total-width);
        background: #ffffff !important;
        border-right: 2px solid #000 !important;
    }

    .player-main-head {
        background: #ffffff !important;
        color: var(--player-color) !important;
        font-size: 1.05rem;
        font-weight: 800;
        min-width: 244px;
        border-left: 2px solid #000 !important;
        border-right: 2px solid #000 !important;
    }

    .left-mini-col {
        min-width: var(--left-mini-width);
        max-width: var(--left-mini-width);
    }

    .left-bonus-col {
        min-width: var(--left-bonus-width);
        max-width: var(--left-bonus-width);
    }

    .vertical-word {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.06rem;
        line-height: 0.82;
        min-height: 3.2rem;
        text-align: center;
    }

    .vertical-bonus-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.1rem;
        min-height: 4.9rem;
        line-height: 0.82;
    }

    .vertical-bonus-word,
    .vertical-bonus-side {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.04rem;
        min-height: 4.9rem;
        text-align: center;
        font-size: 0.72rem;
    }

    .player-sub-head {
        background: #ffffff !important;
        color: var(--player-color) !important;
        text-align: center;
        font-weight: 800;
        font-size: 0.72rem;
    }

    .player-mini-sub-head {
        min-width: 26px;
        max-width: 26px;
    }

    .player-bonus-sub-head {
        min-width: 38px;
        max-width: 38px;
    }

    .player-result-sub-head {
        font-style: italic;
    }

    .player-group-start-sub-head {
        border-left: 2px solid #000 !important;
    }

    .player-prono-end-sub-head,
    .player-result-end-sub-head {
        border-right: 1px solid #000 !important;
    }

    .player-p-sub-head {
        min-width: 34px;
        max-width: 34px;
        border-left: 1px solid #000 !important;
        border-right: 2px solid #000 !important;
        font-size: 0.9rem;
    }

    .section-spacer-row td {
        height: 18px;
        padding: 0;
        background: #ffffff;
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
    }

    .section-title-row td {
        height: 22px;
        padding-top: 0.15rem;
        padding-bottom: 0.15rem;
        background: #ffffff;
        border-top: 1px solid #000 !important;
        border-bottom: 1px solid #000 !important;
    }

    .section-title-cell {
        background: #f9cb9c !important;
        color: #000000;
        font-weight: 900;
        text-align: center;
        text-transform: uppercase;
    }

    .preseason-row td,
    .match-row td {
        border-top: 1px solid #000 !important;
        border-bottom: 1px solid #000 !important;
    }

    .preseason-left-combined-cell {
        padding: 0 !important;
    }

    .preseason-left-grid {
        display: grid;
        grid-template-columns: var(--left-info-width) var(--left-result-width);
        align-items: center;
        min-height: 100%;
    }

    .preseason-label-cell {
        color: #000000;
        font-weight: 600;
        text-align: center;
        padding: 0.12rem 0.22rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .preseason-result-cell {
        color: #000000;
        font-style: italic;
        font-weight: 800;
        text-align: center;
        padding: 0.12rem 0.22rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .player-preseason-prono-cell,
    .player-prono-cell,
    .player-points-cell {
        color: var(--player-color) !important;
        font-weight: 700;
        text-align: center;
    }

    .player-preseason-prono-cell {
        min-width: 210px;
    }

    .player-mini-cell {
        min-width: 26px;
        max-width: 26px;
    }

    .player-bonus-cell {
        min-width: 38px;
        max-width: 38px;
    }

    .player-group-start-cell {
        border-left: 2px solid #000 !important;
    }

    .player-prono-end-cell,
    .player-result-end-cell {
        border-right: 1px solid #000 !important;
    }

    .player-points-cell {
        min-width: 34px;
        max-width: 34px;
        border-left: 1px solid #000 !important;
        border-right: 2px solid #000 !important;
    }

    .preseason-bonus-hit {
        background: var(--preseason-bonus-bg) !important;
    }

    .total-row td {
        background: #ffffff;
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
        font-weight: 900;
    }

    .total-label-cell {
        color: #000000;
        text-align: center;
    }

    .player-summary-block-cell {
        color: var(--player-color) !important;
        font-weight: 900;
        text-align: right;
        padding-right: 0.45rem !important;
        border-left: 2px solid #000 !important;
        border-right: 2px solid #000 !important;
    }

    .total-player-block-cell {
        background: #ffffff !important;
    }

    .journee-title-row td {
        background: #ffffff;
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
        font-weight: 900;
    }

    .journee-left-combined-cell {
        padding: 0 !important;
        background: #f9cb9c !important;
    }

    .journee-left-grid {
        display: grid;
        grid-template-columns: var(--left-journee-width) var(--left-date-width) var(--left-result-width);
        align-items: center;
        min-height: 100%;
    }

    .journee-name-cell {
        color: #000000;
        text-align: center;
        padding: 0.12rem 0.22rem;
    }

    .journee-date-cell {
        color: #a000a0;
        text-align: center;
        padding: 0.12rem 0.22rem;
    }

    .journee-bonus-block-cell {
        background: #ffffff !important;
    }

    .team-cell {
        color: #000000;
        font-weight: 600;
        text-align: center;
    }

    .team-home-cell {
        border-right-color: transparent !important;
    }

    .team-away-cell {
        border-left-color: transparent !important;
    }

    .official-value-cell {
        color: #000000;
        font-weight: 800;
        text-align: center;
    }

    .official-mini-cell {
        min-width: var(--left-mini-width);
        max-width: var(--left-mini-width);
    }

    .official-bonus-cell {
        min-width: var(--left-bonus-width);
        max-width: var(--left-bonus-width);
    }

    .result-indicator-cell {
        color: transparent !important;
        font-size: 0;
    }

    .result-mini-cell {
        min-width: 20px;
        max-width: 20px;
    }

    .result-bonus-cell {
        min-width: 28px;
        max-width: 28px;
    }

    .result-good {
        background: var(--result-good-bg) !important;
    }

    .result-bad {
        background: var(--result-bad-bg) !important;
    }

    .result-bonus {
        background: var(--result-bonus-bg) !important;
    }

    .result-neutral {
        background: #ffffff !important;
    }

    @media (max-width: 1199.98px) {
        .filters-panel {
            border-left: 0;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding-left: 0;
            padding-top: 0.75rem;
        }
    }

    @media (max-width: 991.98px) {
        .results-table-wrapper {
            max-height: 75vh;
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

    function setupRedirectSelect(selectId) {
        const select = document.getElementById(selectId);

        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                window.location.href = select.value;
            }
        });
    }

    function scrollToSelectedJournee() {
        const selectedJourneeId = @json($selectedJournee ? 'journee-'.$selectedJournee->slug : null);

        if (!selectedJourneeId) {
            return;
        }

        const wrapper = document.getElementById('resultsTableWrapper');
        const target = document.getElementById(selectedJourneeId);

        if (!wrapper || !target) {
            return;
        }

        setTimeout(function () {
            wrapper.scrollTo({
                top: Math.max(target.offsetTop - 130, 0),
                behavior: 'smooth'
            });
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupBackToTopButton();
        setupRedirectSelect('seasonSelect');
        setupRedirectSelect('journeeSelect');
        scrollToSelectedJournee();
    });
</script>
@endpush
