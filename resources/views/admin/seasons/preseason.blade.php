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
                class="btn btn-outline-primary rounded-pill fw-bold px-4"
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
            La configuration avant-saison de cette saison ne peut plus être modifiée. Pour corriger les questions ou les bonus, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@else
    <div class="alert alert-info">
        Les modifications faites ici concernent uniquement cette saison. Si tu veux que cette configuration serve de référence pour les prochaines saisons, utilise le bouton Appliquer aux paramètres globaux.
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

<datalist id="correction-group-suggestions">
    <option value="top14_semifinalists">
    <option value="prod2_semifinalists">
</datalist>

<div class="rugby-card p-4 mb-4">
    <h3 class="h5 fw-bold mb-1">
        Questions avant-saison
    </h3>

    <p class="text-muted mb-4">
        @if($season->is_locked)
            Consulte les questions avant-saison propres à cette saison.
        @else
            Modifie, ajoute, supprime ou réordonne les questions propres à cette saison. Les groupes de correction permettent de corriger plusieurs réponses ensemble, par exemple les demi-finalistes sans ordre.
        @endif
    </p>

    @if($questions->isEmpty())
        <div class="alert alert-info mb-0">
            Aucune question avant-saison n’est définie pour cette saison.
        </div>
    @else
        <form method="POST"
              action="{{ route('admin.seasons.preseason.questions.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 42px;">Ordre</th>
                            <th>Question</th>
                            <th style="width: 160px;">Type</th>
                            <th style="width: 220px;">Groupe</th>
                            <th style="width: 150px;">Mode</th>
                            <th style="width: 210px;">Barème</th>
                            <th class="text-center" style="width: 90px;">Points</th>
                            <th class="text-center" style="width: 80px;">Active</th>
                            <th class="text-end" style="width: 110px;">Suppression</th>
                        </tr>
                    </thead>

                    <tbody id="season-preseason-questions-list">
                        @foreach($questions as $question)
                            <tr class="season-preseason-question-item"
                                data-id="{{ $question->id }}">
                                <td>
                                    @if($season->is_locked)
                                        <span class="text-muted">
                                            ☰
                                        </span>
                                    @else
                                        <span class="drag-handle text-muted" role="button">
                                            ☰
                                        </span>
                                    @endif

                                    <input type="hidden"
                                           name="questions[{{ $question->id }}][position]"
                                           value="{{ old("questions.{$question->id}.position", $question->position) }}"
                                           class="question-position-input">
                                </td>

                                <td>
                                    <input name="questions[{{ $question->id }}][label]"
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
                                    <input name="questions[{{ $question->id }}][correction_group]"
                                           value="{{ old("questions.{$question->id}.correction_group", $question->correction_group) }}"
                                           list="correction-group-suggestions"
                                           class="form-control"
                                           placeholder="ex: top14_semifinalists"
                                           @disabled($season->is_locked)>
                                </td>

                                <td>
                                    <select name="questions[{{ $question->id }}][correction_mode]"
                                            class="form-select"
                                            @disabled($season->is_locked)>
                                        <option value="" @selected(old("questions.{$question->id}.correction_mode", $question->correction_mode) === null || old("questions.{$question->id}.correction_mode", $question->correction_mode) === '')>
                                            Normal
                                        </option>

                                        <option value="unordered" @selected(old("questions.{$question->id}.correction_mode", $question->correction_mode) === 'unordered')>
                                            Sans ordre
                                        </option>
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
                                    <input name="questions[{{ $question->id }}][points]"
                                           type="number"
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

                                <td class="text-end">
                                    @unless($season->is_locked)
                                        <button type="submit"
                                                form="delete-question-{{ $question->id }}"
                                                class="btn btn-sm btn-outline-danger rounded-pill">
                                            Supprimer
                                        </button>
                                    @else
                                        <span class="text-muted small">
                                            —
                                        </span>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @unless($season->is_locked)
                <button class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer les questions
                </button>
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

            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label fw-bold">
                        Question
                    </label>

                    <input name="label"
                           value="{{ old('label') }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Type
                    </label>

                    <select name="answer_type" class="form-select" required>
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

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Groupe
                    </label>

                    <input name="correction_group"
                           value="{{ old('correction_group') }}"
                           list="correction-group-suggestions"
                           class="form-control"
                           placeholder="ex: top14_semifinalists">
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Mode
                    </label>

                    <select name="correction_mode" class="form-select">
                        <option value="">
                            Normal
                        </option>

                        <option value="unordered" @selected(old('correction_mode') === 'unordered')>
                            Sans ordre
                        </option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Barème
                    </label>

                    <select name="scoring_profile_id" class="form-select">
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

                <div class="col-lg-1">
                    <label class="form-label fw-bold">
                        Points
                    </label>

                    <input name="points"
                           type="number"
                           value="{{ old('points', 0) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Ordre
                    </label>

                    <input name="position"
                           type="number"
                           value="{{ old('position', ($questions->max('position') ?? 0) + 10) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-lg-2">
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               id="new-question-active"
                               class="form-check-input"
                               @checked(old('is_active', true))>

                        <label for="new-question-active" class="form-check-label">
                            Active
                        </label>
                    </div>
                </div>

                <div class="col-lg-3">
                    <button class="btn btn-warning rounded-pill fw-bold px-4">
                        Ajouter la question
                    </button>
                </div>
            </div>
        </form>
    </div>
@endunless

<div class="rugby-card p-4 mb-4">
    <h3 class="h5 fw-bold mb-1">
        Bonus avant-saison
    </h3>

    <p class="text-muted mb-4">
        @if($season->is_locked)
            Consulte les bonus avant-saison et les questions nécessaires pour les obtenir.
        @else
            Modifie les bonus et les questions nécessaires pour les obtenir.
        @endif
    </p>

    @if($bonusRules->isEmpty())
        <div class="alert alert-info mb-0">
            Aucun bonus avant-saison n’est défini pour cette saison.
        </div>
    @else
        <form method="POST"
              action="{{ route('admin.seasons.preseason.bonus.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="d-flex flex-column gap-3">
                @foreach($bonusRules as $bonusRule)
                    <div class="border rounded-4 p-3">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h4 class="h6 fw-bold mb-2">
                                    {{ $bonusRule->label }}
                                </h4>

                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge text-bg-warning rounded-pill">
                                        {{ $bonusRule->points }} pts
                                    </span>

                                    @if($bonusRule->is_active)
                                        <span class="badge text-bg-success rounded-pill">
                                            Actif
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">
                                            Inactif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#bonus-rule-{{ $bonusRule->id }}">
                                @if($season->is_locked)
                                    Consulter
                                @else
                                    Modifier
                                @endif
                            </button>
                        </div>

                        <div id="bonus-rule-{{ $bonusRule->id }}"
                             class="collapse">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">
                                        Libellé
                                    </label>

                                    <input name="bonus_rules[{{ $bonusRule->id }}][label]"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.label", $bonusRule->label) }}"
                                           class="form-control"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold">
                                        Points
                                    </label>

                                    <input name="bonus_rules[{{ $bonusRule->id }}][points]"
                                           type="number"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.points", $bonusRule->points) }}"
                                           class="form-control text-center"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label fw-bold">
                                        Ordre
                                    </label>

                                    <input name="bonus_rules[{{ $bonusRule->id }}][position]"
                                           type="number"
                                           value="{{ old("bonus_rules.{$bonusRule->id}.position", $bonusRule->position) }}"
                                           class="form-control text-center"
                                           required
                                           @disabled($season->is_locked)>
                                </div>

                                <div class="col-md-3 d-flex align-items-end gap-4">
                                    <div class="form-check mb-2">
                                        <input type="checkbox"
                                               name="bonus_rules[{{ $bonusRule->id }}][is_active]"
                                               value="1"
                                               id="bonus-active-{{ $bonusRule->id }}"
                                               class="form-check-input"
                                               @checked(old("bonus_rules.{$bonusRule->id}.is_active", $bonusRule->is_active))
                                               @disabled($season->is_locked)>

                                        <label for="bonus-active-{{ $bonusRule->id }}" class="form-check-label">
                                            Actif
                                        </label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input type="checkbox"
                                               name="bonus_rules[{{ $bonusRule->id }}][stop_after_match]"
                                               value="1"
                                               id="bonus-stop-{{ $bonusRule->id }}"
                                               class="form-check-input"
                                               @checked(old("bonus_rules.{$bonusRule->id}.stop_after_match", $bonusRule->stop_after_match))
                                               @disabled($season->is_locked)>

                                        <label for="bonus-stop-{{ $bonusRule->id }}" class="form-check-label">
                                            Stop après réussite
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label fw-bold">
                                    Questions liées
                                </label>

                                <div class="row g-2">
                                    @foreach($questions as $question)
                                        @php
                                            $selectedQuestionIds = collect(
                                                old(
                                                    "bonus_rules.{$bonusRule->id}.question_ids",
                                                    $bonusRule->questions->pluck('id')->toArray()
                                                )
                                            )
                                                ->map(fn ($id) => (int) $id);
                                        @endphp

                                        <div class="col-md-6">
                                            <label class="border rounded-3 p-2 d-flex gap-2 align-items-start h-100">
                                                <input type="checkbox"
                                                       name="bonus_rules[{{ $bonusRule->id }}][question_ids][]"
                                                       value="{{ $question->id }}"
                                                       class="form-check-input mt-1"
                                                       @checked($selectedQuestionIds->contains($question->id))
                                                       @disabled($season->is_locked)>

                                                <span>
                                                    <span class="fw-bold">
                                                        {{ $question->position }}. {{ $question->label }}
                                                    </span>

                                                    @if($question->correction_group)
                                                        <span class="d-block small text-muted">
                                                            {{ $question->correction_group }}

                                                            @if($question->correction_mode === 'unordered')
                                                                · sans ordre
                                                            @endif
                                                        </span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @unless($season->is_locked)
                                <button type="submit"
                                        form="delete-bonus-rule-{{ $bonusRule->id }}"
                                        class="btn btn-sm btn-outline-danger rounded-pill mt-3">
                                    Supprimer ce bonus
                                </button>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>

            @unless($season->is_locked)
                <button class="btn btn-warning rounded-pill fw-bold px-4 mt-3">
                    Enregistrer les bonus
                </button>
            @endunless
        </form>

        @unless($season->is_locked)
            @foreach($bonusRules as $bonusRule)
                <form id="delete-bonus-rule-{{ $bonusRule->id }}"
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

                    <input name="label"
                           value="{{ old('label') }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Points
                    </label>

                    <input name="points"
                           type="number"
                           value="{{ old('points', 0) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Ordre
                    </label>

                    <input name="position"
                           type="number"
                           value="{{ old('position', ($bonusRules->max('position') ?? 0) + 10) }}"
                           class="form-control text-center"
                           required>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-4">
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               id="new-bonus-active"
                               class="form-check-input"
                               @checked(old('is_active', true))>

                        <label for="new-bonus-active" class="form-check-label">
                            Actif
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox"
                               name="stop_after_match"
                               value="1"
                               id="new-bonus-stop"
                               class="form-check-input"
                               @checked(old('stop_after_match'))>

                        <label for="new-bonus-stop" class="form-check-label">
                            Stop après réussite
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-bold">
                    Questions liées
                </label>

                @if($questions->isEmpty())
                    <div class="alert alert-info mb-0">
                        Ajoute d’abord des questions avant de lier un bonus.
                    </div>
                @else
                    <div class="row g-2">
                        @foreach($questions as $question)
                            <div class="col-md-6">
                                <label class="border rounded-3 p-2 d-flex gap-2 align-items-start h-100">
                                    <input type="checkbox"
                                           name="question_ids[]"
                                           value="{{ $question->id }}"
                                           class="form-check-input mt-1">

                                    <span>
                                        <span class="fw-bold">
                                            {{ $question->position }}. {{ $question->label }}
                                        </span>

                                        @if($question->correction_group)
                                            <span class="d-block small text-muted">
                                                {{ $question->correction_group }}

                                                @if($question->correction_mode === 'unordered')
                                                    · sans ordre
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <button class="btn btn-warning rounded-pill fw-bold px-4 mt-3">
                Ajouter le bonus
            </button>
        </form>
    </div>
@endunless

<a href="{{ route('admin.seasons.journees', $season) }}"
   class="btn btn-outline-secondary rounded-pill">
    Retour aux journées
</a>

@unless($season->is_locked)
    <div class="modal fade" id="syncToGlobalModal" tabindex="-1" aria-labelledby="syncToGlobalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
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

                    <p class="mb-0 text-muted">
                        Les prochaines saisons pourront repartir de ces nouveaux paramètres globaux si la configuration globale change.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <form method="POST"
                          action="{{ route('admin.seasons.preseason.sync-to-global', $season) }}">
                        @csrf

                        <button class="btn btn-primary rounded-pill fw-bold">
                            Confirmer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endunless

@endsection

@unless($season->is_locked)
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const questionList = document.getElementById('season-preseason-questions-list');

            if (! questionList || ! window.Sortable) {
                return;
            }

            new Sortable(questionList, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () {
                    Array.from(questionList.querySelectorAll('.season-preseason-question-item'))
                        .forEach(function (row, index) {
                            const positionInput = row.querySelector('.question-position-input');

                            if (positionInput) {
                                positionInput.value = (index + 1) * 10;
                            }
                        });
                },
            });
        });
    </script>
    @endpush
@endunless
