@extends('layouts.pronos')

@section('content')

@php
    $players = $players ?? collect();

    $freeTextQuestions = $questions
        ->filter(fn ($question) => $question->answer_type === 'free_text')
        ->values();
@endphp

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.journees', $season),
    'label' => 'Retour aux journées',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Résultats avant-saison — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Saisis les réponses officielles des questions avant-saison. Les réponses libres se corrigent manuellement joueur par joueur.
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-info">
        Cette saison est verrouillée. Les résultats avant-saison sont consultables uniquement.
        Pour les modifier, il faut d’abord déverrouiller la saison depuis sa page d’édition.
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if($questions->isEmpty())

    <div class="alert alert-info">
        Aucune question avant-saison active pour cette saison.
    </div>

@else
    <form method="POST"
          id="preseason-results-form"
          action="{{ route('admin.seasons.preseason-results.update', $season) }}"
          autocomplete="off">
        @csrf
        @method('PUT')

        @unless($season->is_locked)
            <div class="sticky-form-actions">
                <div class="sticky-form-actions-inner">
                    <div class="small text-muted">
                        Les réponses officielles et corrections manuelles seront enregistrées ensemble.
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <button type="submit"
                                name="lock_season"
                                value="0"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer et recalculer
                        </button>

                        <button type="button"
                                class="btn btn-outline-danger rounded-pill fw-bold px-4"
                                data-bs-toggle="modal"
                                data-bs-target="#lockSeasonModal="lock_season"
                                value="0"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer et recalculer
                        </button>

                        <button type="button"
">
                            Enregistrer, recalculer et verrouiller
                        </button>
                    </div>
                </div>
            </div>
        @endunless

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">
                                Question
                            </th>

                            <th>
                                Résultat officiel
                            </th>

                            <th class="text-center" style="width: 140px;">
                                Statut
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($questions as $question)
                            @php
                                $isFreeTextQuestion = $question->answer_type === 'free_text';

                                $clubs = match ($question->answer_type) {
                                    'top14_club' => $top14Clubs,
                                    'prod2_club' => $prod2Clubs,
                                    'season_club' => $seasonClubs,
                                    default => collect(),
                                };

                                $officialTextAnswerValue = old(
                                    "results.{$question->id}.text_answer",
                                    $question->result_text_answer
                                );

                                $questionHasOfficialResult = $isFreeTextQuestion
                                    ? filled($officialTextAnswerValue)
                                    : $question->hasOfficialResult();
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $question->label }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ $question->points }} point(s)

                                        @if($isFreeTextQuestion)
                                            · réponse libre, correction manuelle sous la question
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    @if($isFreeTextQuestion)
                                        <div class="input-group">
                                            <input type="text"
                                                   name="results[{{ $question->id }}][text_answer]"
                                                   value="{{ $officialTextAnswerValue }}"
                                                   class="form-control js-free-text-official-answer"
                                                   placeholder="Réponse officielle"
                                                   data-free-text-question-id="{{ $question->id }}"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   @disabled($season->is_locked)>

                                            @unless($season->is_locked)
                                                <button type="button"
                                                        class="btn btn-outline-secondary js-free-text-clear-button"
                                                        title="Effacer la réponse officielle et remettre les vérifications à zéro"
                                                        aria-label="Effacer la réponse officielle"
                                                        data-free-text-question-id="{{ $question->id }}">
                                                    ×
                                                </button>
                                            @endunless
                                        </div>

                                        <div class="form-text">
                                            La croix vide la réponse officielle et remet les vérifications de cette question à zéro.
                                        </div>
                                    @else
                                        <select name="results[{{ $question->id }}][club_id]"
                                                class="form-select"
                                                @disabled($season->is_locked)>
                                            <option value="">
                                                Non renseigné
                                            </option>

                                            @foreach($clubs as $club)
                                                <option value="{{ $club->id }}"
                                                        @selected((string) old("results.{$question->id}.club_id", $question->result_club_id) === (string) $club->id)>
                                                    {{ $club->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($isFreeTextQuestion)
                                        <span class="badge {{ $questionHasOfficialResult ? 'bg-success' : 'bg-secondary' }}"
                                              data-free-text-status-badge="{{ $question->id }}">
                                            {{ $questionHasOfficialResult ? 'Saisi' : 'En attente' }}
                                        </span>
                                    @elseif($questionHasOfficialResult)
                                        <span class="badge bg-success">
                                            Saisi
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            En attente
                                        </span>
                                    @endif
                                </td>
                            </tr>

                            @if($isFreeTextQuestion)
                                @php
                                    $predictionsByUser = $question->predictions
                                        ->keyBy('user_id');

                                    $officialAnswerInputIsFilled = filled($officialTextAnswerValue);
                                @endphp

                                <tr class="free-text-correction-row">
                                    <td colspan="3" class="p-0">
                                        <div class="free-text-correction-block">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                                <div>
                                                    <div class="fw-bold">
                                                        Vérification des réponses libres
                                                    </div>

                                                    <div class="small text-muted mt-1">
                                                        <span data-free-text-official-label="{{ $question->id }}">
                                                            @if($officialAnswerInputIsFilled)
                                                                · réponse officielle : {{ $officialTextAnswerValue }}
                                                            @else
                                                                · réponse officielle non renseignée
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>

                                                <span class="badge rounded-pill text-bg-warning px-3 py-2">
                                                    Intervention humaine
                                                </span>
                                            </div>

                                            <div class="alert alert-warning mb-3 {{ $officialAnswerInputIsFilled ? 'd-none' : '' }}"
                                                 data-free-text-missing-notice="{{ $question->id }}">
                                                Renseigne la réponse officielle au-dessus pour débloquer la correction joueur par joueur.
                                            </div>

                                            <div class="alert alert-success mb-3 {{ $officialAnswerInputIsFilled ? '' : 'd-none' }}"
                                                 data-free-text-ready-notice="{{ $question->id }}">
                                                La réponse officielle est renseignée : tu peux corriger les pronos ci-dessous, puis tout enregistrer en une seule fois.
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table align-middle mb-0 free-text-corrections-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 180px;">
                                                                Joueur
                                                            </th>

                                                            <th>
                                                                Prono saisi
                                                            </th>

                                                            <th class="text-center" style="width: 320px;">
                                                                Correction
                                                            </th>

                                                            <th class="text-center" style="width: 110px;">
                                                                Points
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach($players as $player)
                                                            @php
                                                                $prediction = $predictionsByUser->get($player->id);
                                                                $hasPrediction = $prediction && filled($prediction->text_answer);

                                                                $oldCorrectionValue = old("free_text_corrections.{$question->id}.{$player->id}");

                                                                $correctionValue = $oldCorrectionValue !== null
                                                                    ? $oldCorrectionValue
                                                                    : (
                                                                        ! $prediction || $prediction->is_correct === null
                                                                            ? 'pending'
                                                                            : ($prediction->is_correct ? '1' : '0')
                                                                    );

                                                                $canCorrectNow = ! $season->is_locked
                                                                    && $officialAnswerInputIsFilled
                                                                    && $hasPrediction;

                                                                $canEverEnableCorrection = ! $season->is_locked
                                                                    && $hasPrediction;
                                                            @endphp

                                                            <tr>
                                                                <td class="fw-bold">
                                                                    {{ $player->display_name }}
                                                                </td>

                                                                <td>
                                                                    @if($hasPrediction)
                                                                        {{ $prediction->text_answer }}
                                                                    @else
                                                                        <span class="text-muted">
                                                                            Aucun prono saisi
                                                                        </span>
                                                                    @endif
                                                                </td>

                                                                <td class="text-center">
                                                                    @if($hasPrediction)
                                                                        <div class="btn-group btn-group-sm"
                                                                             role="group"
                                                                             aria-label="Correction {{ $question->id }} {{ $player->id }}">
                                                                            <input type="radio"
                                                                                   class="btn-check js-free-text-correction-input"
                                                                                   name="free_text_corrections[{{ $question->id }}][{{ $player->id }}]"
                                                                                   id="free_text_correction_{{ $question->id }}_{{ $player->id }}_pending"
                                                                                   value="pending"
                                                                                   autocomplete="off"
                                                                                   data-free-text-correction-question-id="{{ $question->id }}"
                                                                                   data-free-text-correction-can-enable="{{ $canEverEnableCorrection ? '1' : '0' }}"
                                                                                   @checked($correctionValue === 'pending')
                                                                                   @disabled(! $canCorrectNow)>

                                                                            <label class="btn btn-outline-secondary"
                                                                                   for="free_text_correction_{{ $question->id }}_{{ $player->id }}_pending">
                                                                                À vérifier
                                                                            </label>

                                                                            <input type="radio"
                                                                                   class="btn-check js-free-text-correction-input"
                                                                                   name="free_text_corrections[{{ $question->id }}][{{ $player->id }}]"
                                                                                   id="free_text_correction_{{ $question->id }}_{{ $player->id }}_true"
                                                                                   value="1"
                                                                                   autocomplete="off"
                                                                                   data-free-text-correction-question-id="{{ $question->id }}"
                                                                                   data-free-text-correction-can-enable="{{ $canEverEnableCorrection ? '1' : '0' }}"
                                                                                   @checked($correctionValue === '1')
                                                                                   @disabled(! $canCorrectNow)>

                                                                            <label class="btn btn-outline-success"
                                                                                   for="free_text_correction_{{ $question->id }}_{{ $player->id }}_true">
                                                                                Juste
                                                                            </label>

                                                                            <input type="radio"
                                                                                   class="btn-check js-free-text-correction-input"
                                                                                   name="free_text_corrections[{{ $question->id }}][{{ $player->id }}]"
                                                                                   id="free_text_correction_{{ $question->id }}_{{ $player->id }}_false"
                                                                                   value="0"
                                                                                   autocomplete="off"
                                                                                   data-free-text-correction-question-id="{{ $question->id }}"
                                                                                   data-free-text-correction-can-enable="{{ $canEverEnableCorrection ? '1' : '0' }}"
                                                                                   @checked($correctionValue === '0')
                                                                                   @disabled(! $canCorrectNow)>

                                                                            <label class="btn btn-outline-danger"
                                                                                   for="free_text_correction_{{ $question->id }}_{{ $player->id }}_false">
                                                                                Faux
                                                                            </label>
                                                                        </div>
                                                                    @else
                                                                        <span class="badge rounded-pill text-bg-secondary">
                                                                            Aucun prono
                                                                        </span>
                                                                    @endif
                                                                </td>

                                                                <td class="text-center">
                                                                    @if(! $prediction || $prediction->is_correct === null)
                                                                        <span class="badge rounded-pill text-bg-secondary">
                                                                            0
                                                                        </span>
                                                                    @elseif($prediction->is_correct)
                                                                        <span class="badge rounded-pill text-bg-success">
                                                                            {{ $prediction->points }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge rounded-pill text-bg-danger">
                                                                            0
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="small text-muted mt-3">
                                                Les points affichés correspondent au dernier calcul enregistré. Ils seront mis à jour après
                                                <span class="fw-bold">Enregistrer et recalculer</span>.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @unless($season->is_locked)
            <div class="modal fade"
                 id="lockSeasonModal"
                 tabindex="-1"
                 aria-labelledby="lockSeasonModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"
                                id="lockSeasonModalLabel">
                                Verrouiller la saison ?
                            </h5>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-3">
                                Cette action va enregistrer les résultats avant-saison, appliquer les corrections manuelles, recalculer les points, puis verrouiller la saison.
                            </p>

                            <div class="alert alert-warning mb-0">
                                Une fois verrouillée, la saison passera en consultation seule :
                                clubs, joueurs, paramètres, résultats et configuration ne seront plus modifiables tant que la saison n’est pas déverrouillée.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-outline-secondary rounded-pill fw-bold"
                                    data-bs-dismiss="modal">
                                Annuler
                            </button>

                            <button type="submit"
                                    name="lock_season"
                                    value="1"
                                    form="preseason-results-form"
                                    class="btn btn-danger rounded-pill fw-bold">
                                Confirmer et verrouiller
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endunless
    </form>
@endif

@endsection

@push('styles')
<style>
    .sticky-form-actions {
        position: sticky;
        top: 0;
        z-index: 1040;
        margin-bottom: 1rem;
        padding: 0.75rem 0;
        background: #ffffff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .sticky-form-actions-inner {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }

    .free-text-correction-row > td {
        border-top: 0;
    }

    .free-text-correction-block {
        padding: 1.25rem;
        background: rgba(255, 193, 7, 0.08);
        border-top: 1px solid rgba(255, 193, 7, 0.25);
        border-bottom: 1px solid rgba(255, 193, 7, 0.25);
    }

    .free-text-corrections-table td,
    .free-text-corrections-table th {
        vertical-align: middle;
    }

    .free-text-corrections-table .btn-group .btn {
        min-width: 84px;
    }

    .js-free-text-clear-button {
        min-width: 2.75rem;
        font-size: 1.25rem;
        line-height: 1;
    }

    @media (max-width: 767.98px) {
        .sticky-form-actions-inner {
            align-items: stretch;
        }

        .sticky-form-actions-inner > div {
            width: 100%;
        }

        .sticky-form-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const officialInputs = document.querySelectorAll('.js-free-text-official-answer');
        const clearButtons = document.querySelectorAll('.js-free-text-clear-button');

        function refreshFreeTextQuestion(questionId) {
            const officialInput = document.querySelector(
                '.js-free-text-official-answer[data-free-text-question-id="' + questionId + '"]'
            );

            if (!officialInput) {
                return;
            }

            const officialValue = officialInput.value.trim();
            const officialIsFilled = officialValue.length > 0;

            const correctionInputs = document.querySelectorAll(
                '.js-free-text-correction-input[data-free-text-correction-question-id="' + questionId + '"]'
            );

            const groups = {};

            correctionInputs.forEach(function (input) {
                const canEverEnable = input.dataset.freeTextCorrectionCanEnable === '1';

                input.disabled = !(canEverEnable && officialIsFilled);

                if (!groups[input.name]) {
                    groups[input.name] = [];
                }

                groups[input.name].push(input);
            });

            if (!officialIsFilled) {
                Object.values(groups).forEach(function (groupInputs) {
                    const pendingInput = groupInputs.find(function (input) {
                        return input.value === 'pending';
                    });

                    if (pendingInput) {
                        pendingInput.checked = true;
                    }
                });
            }

            const missingNotice = document.querySelector(
                '[data-free-text-missing-notice="' + questionId + '"]'
            );

            const readyNotice = document.querySelector(
                '[data-free-text-ready-notice="' + questionId + '"]'
            );

            const officialLabel = document.querySelector(
                '[data-free-text-official-label="' + questionId + '"]'
            );

            const statusBadge = document.querySelector(
                '[data-free-text-status-badge="' + questionId + '"]'
            );

            const clearButton = document.querySelector(
                '.js-free-text-clear-button[data-free-text-question-id="' + questionId + '"]'
            );

            if (missingNotice) {
                missingNotice.classList.toggle('d-none', officialIsFilled);
            }

            if (readyNotice) {
                readyNotice.classList.toggle('d-none', !officialIsFilled);
            }

            if (officialLabel) {
                if (officialIsFilled) {
                    officialLabel.textContent = '· réponse officielle : ' + officialValue;
                } else {
                    officialLabel.textContent = '· réponse officielle non renseignée';
                }
            }

            if (statusBadge) {
                statusBadge.classList.toggle('bg-success', officialIsFilled);
                statusBadge.classList.toggle('bg-secondary', !officialIsFilled);
                statusBadge.textContent = officialIsFilled ? 'Saisi' : 'En attente';
            }

            if (clearButton) {
                clearButton.disabled = !officialIsFilled;
            }
        }

        officialInputs.forEach(function (input) {
            refreshFreeTextQuestion(input.dataset.freeTextQuestionId);

            input.addEventListener('input', function () {
                refreshFreeTextQuestion(input.dataset.freeTextQuestionId);
            });
        });

        clearButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const questionId = button.dataset.freeTextQuestionId;
                const officialInput = document.querySelector(
                    '.js-free-text-official-answer[data-free-text-question-id="' + questionId + '"]'
                );

                if (!officialInput) {
                    return;
                }

                officialInput.value = '';
                refreshFreeTextQuestion(questionId);
                officialInput.focus();
            });
        });
    });
</script>
@endpush
