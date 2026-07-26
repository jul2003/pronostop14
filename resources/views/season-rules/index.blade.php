@extends('layouts.pronos')

@section('content')

@php
    $pointsLabel = function ($points) {
        $points = (int) $points;

        return $points.' '.($points === 1 || $points === -1 ? 'point' : 'points');
    };

    $bonusRuleName = function ($bonusRule) {
        return $bonusRule->name
            ?? $bonusRule->label
            ?? $bonusRule->title
            ?? 'Bonus avant-saison';
    };

    $ruleLabels = [
        'home_win' => 'Bon résultat : victoire domicile',
        'away_win' => 'Bon résultat : victoire extérieur',
        'draw' => 'Bon résultat : match nul',
        'tries_exact' => 'Nombre d’essais exact',
        'tries_near' => 'Nombre d’essais à ± 1',
        'bonus_correct' => 'Bonus offensif/défensif juste',
        'bonus_wrong' => 'Bonus offensif/défensif faux',
        'perfect_round' => 'Bonus journée parfaite',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Barèmes & bonus
        </div>

        <h2 class="fw-bold mb-1">
            {{ $selectedSeason->name }}
        </h2>

        <p class="text-muted mb-0">
            Synthèse des points et bonus appliqués à la saison.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('results.season', $selectedSeason) }}"
           class="btn btn-outline-primary rounded-pill fw-bold px-4">
            Résultats
        </a>

        <a href="{{ route('rankings.general', $selectedSeason) }}"
           class="btn btn-warning rounded-pill fw-bold px-4">
            Classement joueurs
        </a>
    </div>
</div>

<div class="rugby-card p-4 mb-4">
    <label for="seasonSelect" class="form-label fw-bold">
        Saison
    </label>

    <select id="seasonSelect" class="form-select">
        @foreach($seasons as $seasonOption)
            <option value="{{ route('season-rules.season', $seasonOption) }}"
                    @selected($seasonOption->id === $selectedSeason->id)>
                {{ $seasonOption->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Barèmes des journées
        </h3>

        <p class="text-muted mb-0">
            Les points peuvent varier selon le type de journée.
        </p>
    </div>

    @if($journeeRuleBlocks->isEmpty())
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Aucun barème de journée n’est encore disponible pour cette saison.
            </div>
        </div>
    @else
        <div class="row g-0">
            @foreach($journeeRuleBlocks as $block)
                <div class="col-xl-6 border-bottom border-end">
                    <div class="p-4 h-100">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h4 class="h5 fw-bold mb-1">
                                    {{ $block['label'] }}
                                </h4>

                                <div class="text-muted small">
                                    {{ $block['profile_name'] }}
                                </div>
                            </div>

                            @if($block['stop_on_wrong_result'])
                                <span class="badge rounded-pill text-bg-dark">
                                    Résultat faux = 0 sur le match
                                </span>
                            @else
                                <span class="badge rounded-pill text-bg-secondary">
                                    Détails comptés même si résultat faux
                                </span>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 rules-table">
                                <tbody>
                                    @foreach($ruleLabels as $code => $label)
                                        @if(array_key_exists($code, $block['rules']))
                                            <tr>
                                                <td>
                                                    {{ $label }}
                                                </td>

                                                <td class="text-end fw-bold">
                                                    {{ $pointsLabel($block['rules'][$code]) }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Pronostics avant-saison
        </h3>

        <p class="text-muted mb-0">
            Points attribués pour chaque question avant-saison.
        </p>
    </div>

    @if($preseasonQuestions->isEmpty())
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Aucune question avant-saison active pour cette saison.
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 rules-table">
                <thead class="table-light">
                    <tr>
                        <th>Question</th>
                        <th class="text-end">Points</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($preseasonQuestions as $question)
                        <tr>
                            <td class="fw-bold">
                                {{ $question->label }}
                            </td>

                            <td class="text-end fw-bold">
                                {{ $pointsLabel($question->points) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <h3 class="fw-bold mb-1">
            Bonus avant-saison
        </h3>

        <p class="text-muted mb-0">
            Bonus calculés à partir de groupes de questions avant-saison.
        </p>
    </div>

    @if($preseasonBonusRules->isEmpty())
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Aucun bonus avant-saison actif pour cette saison.
            </div>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 rules-table">
                <thead class="table-light">
                    <tr>
                        <th>Bonus</th>
                        <th>Questions concernées</th>
                        <th class="text-end">Points</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($preseasonBonusRules as $bonusRule)
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    {{ $bonusRuleName($bonusRule) }}
                                </div>

                                @if($bonusRule->stop_after_match)
                                    <div class="text-muted small">
                                        Si ce bonus est obtenu, les bonus suivants liés à cette série ne sont pas ajoutés.
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($bonusRule->questions->isEmpty())
                                    <span class="text-muted">
                                        Aucune question liée
                                    </span>
                                @else
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($bonusRule->questions as $question)
                                            <span>
                                                {{ $question->label }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="text-end fw-bold">
                                {{ $pointsLabel($bonusRule->points) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .rules-table th,
    .rules-table td {
        white-space: nowrap;
        padding-top: 0.55rem;
        padding-bottom: 0.55rem;
    }

    .rules-table td:first-child,
    .rules-table th:first-child {
        white-space: normal;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('seasonSelect');

        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            if (select.value) {
                window.location.href = select.value;
            }
        });
    });
</script>
@endpush
