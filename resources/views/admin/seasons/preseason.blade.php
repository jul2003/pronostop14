@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.journees', $season),
    'label' => 'Retour aux journées',
])

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Avant-saison — {{ $season->name }}
        </h2>

        <p class="text-muted mb-0">
            @if($season->is_locked)
                Cette saison est verrouillée. Les questions et bonus avant-saison sont consultables uniquement.
            @else
                Questions et bonus avant-saison propres à cette saison.
            @endif
        </p>
    </div>

    @unless($season->is_locked)
        <button type="button"
                class="btn btn-outline-warning rounded-pill fw-bold px-4"
                data-bs-toggle="modal"
                data-bs-target="#syncToGlobalModal">
            Appliquer aux paramètres globaux
        </button>
    @endunless
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            La configuration avant-saison de cette saison ne peut plus être modifiée.
            Pour corriger les questions ou les bonus, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@else
    <div class="alert alert-info">
        Les modifications faites ici concernent uniquement cette saison.
        Si tu veux que cette configuration serve de référence pour les prochaines saisons, utilise le bouton
        <strong>Appliquer aux paramètres globaux</strong>.
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="rugby-card p-4 mb-4">
    <h3 class="h5 fw-bold mb-1">
        Questions avant-saison
    </h3>

    <p class="text-muted mb-3">
        @if($season->is_locked)
            Consulte les questions avant-saison propres à cette saison.
        @else
            Modifie, ajoute, supprime ou réordonne les questions propres à cette saison.
        @endif
    </p>

    @if($questions->isEmpty())
        <div class="alert alert-info">
            Aucune question avant-saison n’est définie pour cette saison.
        </div>
    @else
        <form method="POST"
              action="{{ route('admin.seasons.preseason.questions.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 70px;">Ordre</th>
                            <th style="min-width: 260px;">Question</th>
                            <th style="width: 190px;">Type</th>
                            <th style="width: 210px;">Barème</th>
                            <th class="text-center" style="width: 110px;">Points</th>
                            <th class="text-center" style="width: 90px;">Active</th>

                            @unless($season->is_locked)
                                <th class="text-end" style="width: 120px;">Suppression</th>
                            @endunless
                        </tr>
                    </thead>

                    <tbody id="seasonPreseasonQuestionsRows">
                        @foreach($questions as $question)
                            <tr class="season-preseason-question-row">
                                <td class="text-center">
                                    @if($season->is_locked)
                                        <span class="text-muted"
                                              title="Ordre">
                                            ☰
                                        </span>
                                    @else
                                        <span class="drag-handle text-muted"
                                              style="cursor: grab;"
                                              title="Déplacer">
                                            ☰
                                        </span>
                                    @endif

                                    <input type="hidden"
                                           name="questions[{{ $question->id }}][position]"
                                           value="{{ old("questions.{$question->id}.position", $question->position) }}"
                                           class="question-position-input">
                                </td>

                                <td>
                                    <input type="text"
                                           name="questions[{{ $question->id }}][label]"
                                           value="{{ old("questions.{$question->id}.label", $question->label) }}"
                                           class="form-control"
                                           required
                                           @disabled($season->is_locked)>
                                </td>

                                <td>
                                    <select name="questions[{{ $question->id }}][answer_type]"
                                            class="form-select"
                                            required
                                            @disabled($season->is_locked)>
                                        @foreach([
                                            'top14_club' => 'Club TOP 14',
                                            'prod2_club' => 'Club PRO D2',
                                            'season_club' => 'Club saison',
                                            'free_text' => 'Texte libre',
                                        ] as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected(old("questions.{$question->id}.answer_type", $question->answer_type) === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select name="questions[{{ $question->id }}][scoring_profile_id]"
                                            class="form-select"
                                            @disabled($season->is_locked)>
                                        <option value="">
                                            Aucun
                                        </option>

                                        @foreach($scoringProfiles as $profile)
                                            <option value="{{ $profile->id }}"
                                                    @selected((string) old("questions.{$question->id}.scoring_profile_id", $question->scoring_profile_id) === (string) $profile->id)>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input type="number"
                                           name="questions[{{ $question->id }}][points]"
                                           value="{{ old("questions.{$question->id}.points", $question->points) }}"
                                           class="form-control text-center"
                                           required
                                           @disabled($season->is_locked)>
                                </td>

                                <td class="text-center">
                                    <input type="checkbox"
                                           name="questions[{{ $question->id }}][is_active]"
                                           value="1"
                                           class="form-check-input"
                                           @checked(old("questions.{$question->id}.is_active", $question->is_active))
                                           @disabled($season->is_locked)>
                                </td>

                                @unless($season->is_locked)
                                    <td class="text-end">
                                        <button type="submit"
                                                form="delete-question-{{ $question->id }}"
                                                class="btn btn-sm btn-outline-danger rounded-pill"
                                                onclick="return confirm('Supprimer cette question avant-saison ? Les liens avec les bonus seront aussi supprimés.');">
                                            Supprimer
                                        </button>
                                    </td>
                                @endunless
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @unless($season->is_locked)
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit"
                            class="btn btn-warning rounded-pill fw-bold px-4">
                        Enregistrer les questions
                    </button>
                </div>
            @endunless
        </form>

        @unless($season->is_locked)
            @foreach($questions as $question)
                <form id="delete-question-{{ $question->id }}"
                      method="POST"
                      action="{{ route('admin.seasons.preseason.questions.destroy', [$season, $question]) }}"
                      class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endunless
    @endif
</div>

@unless($season->is_locked)
    <div class="rugby-card p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">
            Ajouter une question
        </h3>

        <form method="POST"
              action="{{ route('admin.seasons.preseason.questions.store', $season) }}">
            @csrf

            <input type="hidden"
                   name="position"
                   value="{{ old('position', ($questions->max('position') ?? 0) + 10) }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        Question
                    </label>

                    <input type="text"
                           name="label"
                           value="{{ old('label') }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Type
                    </label>

                    <select name="answer_type"
                            class="form-select"
                            required>
                        <option value="top14_club" @selected(old('answer_type') === 'top14_club')>
                            Club TOP 14
                        </option>
                        <option value="prod2_club" @selected(old('answer_type') === 'prod2_club')>
                            Club PRO D2
                        </option>
                        <option value="season_club" @selected(old('answer_type') === 'season_club')>
                            Club saison
                        </option>
                        <option value="free_text" @selected(old('answer_type') === 'free_text')>
                            Texte libre
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">
                        Barème
                    </label>

                    <select name="scoring_profile_id"
                            class="form-select">
                        <option value="">
                            Aucun
                        </option>

                        @foreach($scoringProfiles as $profile)
                            <option value="{{ $profile->id }}"
                                    @selected((string) old('scoring_profile_id') === (string) $profile->id)>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Points
                    </label>

                    <input type="number"
                           name="points"
                           value="{{ old('points', 0) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-md-1 text-center">
                    <label class="form-label fw-bold d-block">
                        Active
                    </label>

                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           class="form-check-input"
                           @checked(old('is_active', true))>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit"
                        class="btn btn-outline-primary rounded-pill fw-bold px-4">
                    Ajouter la question
                </button>
            </div>
        </form>
    </div>
@endunless

<div class="rugby-card p-4 mb-4">
    <h3 class="h5 fw-bold mb-1">
        Bonus avant-saison
    </h3>

    <p class="text-muted mb-3">
        @if($season->is_locked)
            Consulte les bonus avant-saison et les questions nécessaires pour les obtenir.
        @else
            Modifie les bonus et les questions nécessaires pour les obtenir.
        @endif
    </p>

    @if($bonusRules->isEmpty())
        <div class="alert alert-info">
            Aucun bonus avant-saison n’est défini pour cette saison.
        </div>
    @else
        <form method="POST"
              action="{{ route('admin.seasons.preseason.bonus.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="d-flex flex-column gap-3">
                @foreach($bonusRules as $bonusRule)
                    <div class="border rounded-4 overflow-hidden">
                        <button type="button"
                                class="w-100 border-0 bg-light p-3 text-start d-flex justify-content-between align-items-center gap-3 season-toggle-button"
                                data-target="bonus-panel-{{ $bonusRule->id }}">
                            <div>
                                <span class="fw-bold">
                                    {{ $bonusRule->label }}
                                </span>

                                <span class="badge bg-primary ms-2">
                                    {{ $bonusRule->points }} pts
                                </span>

                                @if($bonusRule->is_active)
                                    <span class="badge bg-success ms-1">
                                        Actif
                                    </span>
                                @else
                                    <span class="badge bg-secondary ms-1">
                                        Inactif
                                    </span>
                                @endif
                            </div>

                            <span class="text-muted small season-toggle-label">
                                @if($season->is_locked)
                                    Consulter
                                @else
                                    Modifier
                                @endif
                            </span>
                        </button>

                        <div id="bonus-panel-{{ $bonusRule->id }}"
                             class="season-toggle-panel p-4 border-top d-none">
                            <div class="row g-3 mb-4">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">
                                        Libellé
                                    </label>

                                    <input type="text"
                                           name="bonus_rules[{{ $bonusRule->id }}][label]"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.label", $bonusRule->label) }}"
                                           class="form-control"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold">
                                        Points
                                    </label>

                                    <input type="number"
                                           name="bonus_rules[{{ $bonusRule->id }}][points]"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.points", $bonusRule->points) }}"
                                           class="form-control text-center"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold">
                                        Ordre
                                    </label>

                                    <input type="number"
                                           name="bonus_rules[{{ $bonusRule->id }}][position]"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.position", $bonusRule->position) }}"
                                           class="form-control text-center"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox"
                                               name="bonus_rules[{{ $bonusRule->id }}][is_active]"
                                               value="1"
                                               class="form-check-input"
                                               id="bonus_active_{{ $bonusRule->id }}"
                                               @checked(old("bonus_rules.{$bonusRule->id}.is_active", $bonusRule->is_active))
                                               @disabled($season->is_locked)>

                                        <label class="form-check-label fw-bold"
                                               for="bonus_active_{{ $bonusRule->id }}">
                                            Actif
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input type="checkbox"
                                               name="bonus_rules[{{ $bonusRule->id }}][stop_after_match]"
                                               value="1"
                                               class="form-check-input"
                                               id="bonus_stop_{{ $bonusRule->id }}"
                                               @checked(old("bonus_rules.{$bonusRule->id}.stop_after_match", $bonusRule->stop_after_match))
                                               @disabled($season->is_locked)>

                                        <label class="form-check-label fw-bold"
                                               for="bonus_stop_{{ $bonusRule->id }}">
                                            Stop après réussite
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-bold mb-2">
                                    Questions liées
                                </div>

                                <div class="row g-2">
                                    @foreach($questions as $question)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox"
                                                       name="bonus_rules[{{ $bonusRule->id }}][question_ids][]"
                                                       value="{{ $question->id }}"
                                                       class="form-check-input"
                                                       id="bonus_{{ $bonusRule->id }}_question_{{ $question->id }}"
                                                       @checked(
                                                           collect(old("bonus_rules.{$bonusRule->id}.question_ids", $bonusRule->questions->pluck('id')->toArray()))
                                                               ->map(fn ($id) => (int) $id)
                                                               ->contains($question->id)
                                                       )
                                                       @disabled($season->is_locked)>

                                                <label class="form-check-label"
                                                       for="bonus_{{ $bonusRule->id }}_question_{{ $question->id }}">
                                                    {{ $question->position }}. {{ $question->label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @unless($season->is_locked)
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <button type="submit"
                                            form="delete-bonus-{{ $bonusRule->id }}"
                                            class="btn btn-outline-danger rounded-pill"
                                            onclick="return confirm('Supprimer ce bonus avant-saison ?');">
                                        Supprimer ce bonus
                                    </button>
                                </div>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            @unless($season->is_locked)
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit"
                            class="btn btn-warning rounded-pill fw-bold px-4">
                        Enregistrer les bonus
                    </button>
                </div>
            @endunless
        </form>

        @unless($season->is_locked)
            @foreach($bonusRules as $bonusRule)
                <form id="delete-bonus-{{ $bonusRule->id }}"
                      method="POST"
                      action="{{ route('admin.seasons.preseason.bonus.destroy', [$season, $bonusRule]) }}"
                      class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endunless
    @endif
</div>

@unless($season->is_locked)
    <div class="rugby-card p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">
            Ajouter un bonus
        </h3>

        <form method="POST"
              action="{{ route('admin.seasons.preseason.bonus.store', $season) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold">
                        Libellé
                    </label>

                    <input type="text"
                           name="label"
                           value="{{ old('label') }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Points
                    </label>

                    <input type="number"
                           name="points"
                           value="{{ old('points', 0) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Ordre
                    </label>

                    <input type="number"
                           name="position"
                           value="{{ old('position', ($bonusRules->max('position') ?? 0) + 10) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="form-check-input"
                               id="new_bonus_active"
                               @checked(old('is_active', true))>

                        <label class="form-check-label fw-bold"
                               for="new_bonus_active">
                            Actif
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox"
                               name="stop_after_match"
                               value="1"
                               class="form-check-input"
                               id="new_bonus_stop"
                               @checked(old('stop_after_match', false))>

                        <label class="form-check-label fw-bold"
                               for="new_bonus_stop">
                            Stop après réussite
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <div class="fw-bold mb-2">
                    Questions liées
                </div>

                @if($questions->isEmpty())
                    <div class="text-muted">
                        Ajoute d’abord des questions avant de lier un bonus.
                    </div>
                @else
                    <div class="row g-2">
                        @foreach($questions as $question)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="question_ids[]"
                                           value="{{ $question->id }}"
                                           class="form-check-input"
                                           id="new_bonus_question_{{ $question->id }}">

                                    <label class="form-check-label"
                                           for="new_bonus_question_{{ $question->id }}">
                                        {{ $question->position }}. {{ $question->label }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit"
                        class="btn btn-outline-primary rounded-pill fw-bold px-4">
                    Ajouter le bonus
                </button>
            </div>
        </form>
    </div>
@endunless

<div class="d-flex justify-content-end">
    <a href="{{ route('admin.seasons.journees', $season) }}"
       class="btn btn-outline-secondary rounded-pill fw-bold px-4">
        Retour aux journées
    </a>
</div>

@unless($season->is_locked)
    <div class="modal fade"
         id="syncToGlobalModal"
         tabindex="-1"
         aria-labelledby="syncToGlobalModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="syncToGlobalModalLabel">
                        Appliquer aux paramètres globaux ?
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Cette action va remplacer les paramètres globaux avant-saison par la configuration de cette saison.
                    </p>

                    <div class="alert alert-warning mb-0">
                        Les prochaines saisons pourront repartir de ces nouveaux paramètres globaux si la configuration globale change.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <form method="POST"
                          action="{{ route('admin.seasons.preseason.sync-to-global', $season) }}">
                        @csrf

                        <button type="submit"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Confirmer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endunless

@unless($season->is_locked)
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endunless

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const questionsRows = document.getElementById('seasonPreseasonQuestionsRows');

        @unless($season->is_locked)
            if (questionsRows && window.Sortable) {
                new Sortable(questionsRows, {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: function () {
                        refreshQuestionPositions();
                    },
                });

                refreshQuestionPositions();
            }
        @endunless

        document.querySelectorAll('.season-toggle-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.dataset.target;
                const panel = document.getElementById(targetId);
                const label = button.querySelector('.season-toggle-label');

                if (! panel) {
                    return;
                }

                const isHidden = panel.classList.contains('d-none');

                panel.classList.toggle('d-none');

                if (label) {
                    label.textContent = isHidden
                        ? '{{ $season->is_locked ? 'Masquer' : 'Masquer' }}'
                        : '{{ $season->is_locked ? 'Consulter' : 'Modifier' }}';
                }
            });
        });

        function refreshQuestionPositions() {
            document
                .querySelectorAll('#seasonPreseasonQuestionsRows .question-position-input')
                .forEach(function (input, index) {
                    input.value = (index + 1) * 10;
                });
        }
    });
</script>

@endsection
