@extends('layouts.pronos')

@section('content')

@php
    $hasEditableQuestions = $hasEditableQuestions
        ?? $questions->contains(
            fn ($question) => ! $question->hasOfficialResult()
        );

    $hasClosedQuestions = $hasClosedQuestions
        ?? $questions->contains(
            fn ($question) => $question->hasOfficialResult()
        );
@endphp

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Mes pronostics
        </div>

        <h2 class="fw-bold mb-1">
            {{ $journee->name }}
        </h2>

        <div class="text-muted">
            {{ $season->name }}

            @if($preseasonDeadline)
                · limite : {{ $preseasonDeadline->format('d/m/Y') }}
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('pronos.index') }}"
           class="btn btn-outline-primary rounded-pill fw-bold">
            ← Retour aux pronostics
        </a>
    </div>
</div>

@if($isNotOpen)
    <div class="alert alert-info">
        Les pronostics avant-saison ne sont pas encore ouverts.
        Une date limite doit être définie sur l’avant-saison ou sur la Journée 1 pour ouvrir cette page aux joueurs.
    </div>
@elseif($isLocked)
    <div class="alert alert-warning">
        Les pronostics avant-saison sont clôturés. Consultation uniquement.
    </div>
@elseif(! $hasEditableQuestions)
    <div class="alert alert-info">
        Tous les résultats de ces questions sont déjà enregistrés.
        Il n’y a plus de pronostic avant-saison disponible à la saisie.
    </div>
@elseif($hasClosedQuestions)
    <div class="alert alert-info">
        Certaines questions sont déjà clôturées parce que leur résultat officiel est enregistré.
        Elles restent visibles, mais elles ne peuvent plus être saisies ni modifiées.
    </div>
@endif

@if($questions->isEmpty())
    <div class="alert alert-info">
        Aucune question avant-saison disponible pour cette saison.
    </div>
@else
    <form method="POST"
          action="{{ route('pronos.store', [$season, $journee]) }}"
          autocomplete="off">

        @csrf

        @if(! $isLocked && ! $isNotOpen && $hasEditableQuestions)
            <div class="d-flex justify-content-center mb-4">
                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer mes pronostics avant-saison
                </button>
            </div>
        @endif

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">
                                Question
                            </th>

                            <th>
                                Réponse
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($questions as $question)
                            @php
                                $prediction = $predictions->get($question->id);

                                $questionHasOfficialResult =
                                    $question->hasOfficialResult();

                                $questionIsDisabled =
                                    $isLocked
                                    || $isNotOpen
                                    || $questionHasOfficialResult;

                                $storedAnswer =
                                    $question->answer_type === 'free_text'
                                        ? $prediction?->text_answer
                                        : $prediction?->club_id;

                                $currentAnswer =
                                    $questionHasOfficialResult
                                        ? $storedAnswer
                                        : old(
                                            "answers.{$question->id}",
                                            $storedAnswer
                                        );

                                $clubs = match ($question->answer_type) {
                                    'top14_club' => $top14Clubs,
                                    'prod2_club' => $prod2Clubs,
                                    'season_club' => $seasonClubs,
                                    default => collect(),
                                };
                            @endphp

                            <tr class="{{ $questionHasOfficialResult ? 'table-light' : '' }}">
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="fw-bold">
                                            {{ $question->label }}
                                        </div>

                                        @if($questionHasOfficialResult)
                                            <span class="badge rounded-pill text-bg-secondary">
                                                Résultat déjà enregistré
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-muted small mt-1">
                                        {{ $question->points }} point(s)
                                    </div>
                                </td>

                                <td>
                                    @if($question->answer_type === 'free_text')
                                        <input type="text"
                                               name="answers[{{ $question->id }}]"
                                               value="{{ $currentAnswer }}"
                                               class="form-control"
                                               placeholder="{{ $questionHasOfficialResult && blank($currentAnswer) ? 'Aucune réponse saisie avant la clôture' : '' }}"
                                               autocomplete="off"
                                               autocorrect="off"
                                               autocapitalize="off"
                                               spellcheck="false"
                                               @disabled($questionIsDisabled)>
                                    @else
                                        <select name="answers[{{ $question->id }}]"
                                                class="form-select preseason-answer-select"
                                                data-question-label="{{ mb_strtolower($question->label) }}"
                                                autocomplete="off"
                                                @disabled($questionIsDisabled)>

                                            <option value="">
                                                {{ $questionHasOfficialResult
                                                    ? 'Aucune réponse saisie avant la clôture'
                                                    : 'Choisir...' }}
                                            </option>

                                            @foreach($clubs as $club)
                                                <option value="{{ $club->id }}"
                                                        @selected((string) $currentAnswer === (string) $club->id)>
                                                    {{ $club->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($questionHasOfficialResult)
                                        <div class="small text-muted mt-2">
                                            @if($prediction)
                                                Ta réponse est conservée, mais elle ne peut plus être modifiée.
                                            @else
                                                Saisie indisponible : le résultat officiel est déjà enregistré.
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(! $isLocked && ! $isNotOpen && $hasEditableQuestions)
            <div class="d-flex justify-content-center mt-4">
                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer mes pronostics avant-saison
                </button>
            </div>
        @endif

    </form>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function groupForSelect(select) {
            const label = select.dataset.questionLabel || '';

            if (label.includes('demi') && label.includes('top 14')) {
                return 'top14_semifinalists';
            }

            if (label.includes('demi') && label.includes('pro d2')) {
                return 'prod2_semifinalists';
            }

            return null;
        }

        function refreshUniquePreseasonSelections() {
            const groups = [
                'top14_semifinalists',
                'prod2_semifinalists',
            ];

            groups.forEach(function (group) {
                const selects = Array.from(
                    document.querySelectorAll('.preseason-answer-select')
                ).filter(function (select) {
                    return groupForSelect(select) === group;
                });

                const selectedValues = selects
                    .map(function (select) {
                        return select.value;
                    })
                    .filter(function (value) {
                        return value !== '';
                    });

                selects.forEach(function (select) {
                    Array.from(select.options).forEach(function (option) {
                        if (option.value === '') {
                            option.disabled = false;

                            return;
                        }

                        option.disabled =
                            selectedValues.includes(option.value)
                            && select.value !== option.value;
                    });
                });
            });
        }

        document
            .querySelectorAll('.preseason-answer-select')
            .forEach(function (select) {
                select.addEventListener(
                    'change',
                    refreshUniquePreseasonSelections
                );
            });

        refreshUniquePreseasonSelections();
    });
</script>
@endpush
