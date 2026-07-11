@extends('layouts.pronos')

@section('content')
<div id="page-top" class="mb-4">
    <a href="{{ route('admin.seasons.journees', $season) }}"
       class="text-decoration-none fw-bold">
        ← Retour aux journées
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                Avant-saison — {{ $season->name }}
            </h2>

            <p class="text-muted mb-0">
                @if($season->is_locked)
                    Cette saison est verrouillée. Les questions, groupes de correction et bonus avant-saison sont consultables uniquement.
                @else
                    Questions, groupes de correction et bonus avant-saison propres à cette saison.
                @endif
            </p>
        </div>

        @unless($season->is_locked)
            <button type="button"
                    class="btn btn-outline-primary rounded-pill fw-bold"
                    data-bs-toggle="modal"
                    data-bs-target="#syncToGlobalModal">
                Appliquer aux paramètres globaux
            </button>
        @endunless
    </div>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        La configuration avant-saison de cette saison ne peut plus être modifiée. Pour corriger les questions, les groupes ou les bonus, il faut d’abord déverrouiller la saison depuis sa page d’édition.
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

<div class="row g-4">
    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Questions avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        @if($season->is_locked)
                            Consulte les questions avant-saison propres à cette saison.
                        @else
                            Modifie, ajoute, supprime ou réordonne les questions propres à cette saison. Les groupes de correction sont gérés dans le bloc séparé ci-dessous.
                        @endif
                    </p>
                </div>
            </div>

            @if($questions->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucune question avant-saison n’est définie pour cette saison.
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.seasons.preseason.questions.update', $season) }}"
                      id="seasonPreseasonQuestionsForm">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;"></th>
                                    <th>Question</th>
                                    <th style="width: 180px;">Type</th>
                                    <th style="width: 260px;">Barème</th>
                                    <th class="text-center" style="width: 110px;">Points</th>
                                    <th class="text-center" style="width: 90px;">Active</th>
                                    <th class="text-end" style="width: 120px;">Suppression</th>
                                </tr>
                            </thead>

                            <tbody id="seasonPreseasonQuestionsList">
                                @foreach($questions->sortBy('position') as $question)
                                    <tr data-id="{{ $question->id }}" draggable="{{ $season->is_locked ? 'false' : 'true' }}">
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                    title="Déplacer"
                                                    @disabled($season->is_locked)>
                                                ☰
                                            </button>

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

                                            @if($question->correctionGroups->isNotEmpty())
                                                <div class="small text-muted mt-2 d-flex flex-wrap gap-1">
                                                    @foreach($question->correctionGroups as $correctionGroup)
                                                        <a href="#correction-group-{{ $correctionGroup->id }}"
                                                           class="badge rounded-pill text-bg-light border text-dark text-decoration-none correction-group-anchor-link"
                                                           title="Aller au groupe {{ $correctionGroup->label }}">
                                                            {{ $correctionGroup->label }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
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
                                                    <option value="{{ $value }}" @selected(old("questions.{$question->id}.answer_type", $question->answer_type) === $value)>
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
                                                    <option value="{{ $profile->id }}" @selected((string) old("questions.{$question->id}.scoring_profile_id", $question->scoring_profile_id) === (string) $profile->id)>
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

                                        <td class="text-end">
                                            @unless($season->is_locked)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger rounded-pill"
                                                        data-label="{{ $question->label }}"
                                                        onclick="submitDeleteForm('delete-question-{{ $question->id }}', this.dataset.label)">
                                                    Supprimer
                                                </button>
                                            @else
                                                —
                                            @endunless
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @unless($season->is_locked)
                        <button class="btn btn-primary rounded-pill fw-bold px-4 mt-3">
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
    </div>

    @unless($season->is_locked)
        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Ajouter une question
                </h3>

                <form method="POST" action="{{ route('admin.seasons.preseason.questions.store', $season) }}">
                    @csrf

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

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                Barème
                            </label>

                            <select name="scoring_profile_id"
                                    class="form-select">
                                <option value="">
                                    Aucun
                                </option>

                                @foreach($scoringProfiles as $profile)
                                    <option value="{{ $profile->id }}" @selected((string) old('scoring_profile_id') === (string) $profile->id)>
                                        {{ $profile->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label fw-bold">
                                Points
                            </label>

                            <input type="number"
                                   name="points"
                                   value="{{ old('points', 0) }}"
                                   class="form-control text-center"
                                   required>
                        </div>

                        <div class="col-md-1">
                            <label class="form-label fw-bold">
                                Ordre
                            </label>

                            <input type="number"
                                   name="position"
                                   value="{{ old('position', ($questions->max('position') ?? 0) + 10) }}"
                                   class="form-control text-center"
                                   required>
                        </div>

                        <div class="col-md-1">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       class="form-check-input"
                                       id="new_question_active"
                                       @checked(old('is_active', true))>

                                <label class="form-check-label fw-bold" for="new_question_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-1">
                            <button class="btn btn-warning rounded-pill fw-bold w-100">
                                +
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    <div class="col-12">
        <div class="rugby-card p-4">
            <h3 class="h5 fw-bold mb-1">
                Groupes de correction
            </h3>

            <p class="text-muted mb-4">
                @if($season->is_locked)
                    Consulte les groupes de correction et les questions corrigées ensemble.
                @else
                    Coche les questions qui doivent être corrigées ensemble. Ces groupes sont corrigés sans tenir compte de l’ordre des réponses.
                @endif
            </p>

            @if($correctionGroups->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun groupe de correction n’est défini pour cette saison.
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.seasons.preseason.correction-groups.update', $season) }}"
                      id="seasonCorrectionGroupsForm">
                    @csrf
                    @method('PUT')

                    <div id="seasonCorrectionGroupsList" class="d-grid gap-3">
                        @foreach($correctionGroups->sortBy('position') as $correctionGroup)
                            @php
                                $selectedQuestionIds = collect(old("correction_groups.{$correctionGroup->id}.question_ids", $correctionGroup->questions->pluck('id')->toArray()))
                                    ->map(fn ($id) => (int) $id);
                            @endphp

                            <div id="correction-group-{{ $correctionGroup->id }}"
                                 class="border rounded-4 p-3 p-md-4 bg-white list-group-item correction-group-target"
                                 style="scroll-margin-top: 1.5rem;"
                                 data-id="{{ $correctionGroup->id }}"
                                 draggable="{{ $season->is_locked ? 'false' : 'true' }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer"
                                                @disabled($season->is_locked)>
                                            ☰
                                        </button>

                                        <input type="hidden"
                                               name="correction_groups[{{ $correctionGroup->id }}][position]"
                                               value="{{ old("correction_groups.{$correctionGroup->id}.position", $correctionGroup->position) }}"
                                               class="season-correction-group-position-input">

                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <h4 class="h5 fw-bold mb-0">
                                                    {{ $correctionGroup->label }}
                                                </h4>

                                                @if($correctionGroup->is_active)
                                                    <span class="badge rounded-pill text-bg-primary">
                                                        Actif
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-secondary">
                                                        Inactif
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="text-muted small">
                                                {{ $correctionGroup->code }} · {{ $correctionGroup->questions->count() }} question(s)
                                            </div>
                                        </div>
                                    </div>

                                    @unless($season->is_locked)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger rounded-pill"
                                                data-label="{{ $correctionGroup->label }}"
                                                onclick="submitDeleteForm('delete-correction-group-{{ $correctionGroup->id }}', this.dataset.label)">
                                            Supprimer
                                        </button>
                                    @endunless
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">
                                            Libellé
                                        </label>

                                        <input type="text"
                                               name="correction_groups[{{ $correctionGroup->id }}][label]"
                                               value="{{ old("correction_groups.{$correctionGroup->id}.label", $correctionGroup->label) }}"
                                               class="form-control"
                                               required
                                               @disabled($season->is_locked)>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">
                                            Code
                                        </label>

                                        <input type="text"
                                               name="correction_groups[{{ $correctionGroup->id }}][code]"
                                               value="{{ old("correction_groups.{$correctionGroup->id}.code", $correctionGroup->code) }}"
                                               class="form-control"
                                               @disabled($season->is_locked)>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="correction_groups[{{ $correctionGroup->id }}][is_active]"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="season_correction_group_active_{{ $correctionGroup->id }}"
                                                   @checked(old("correction_groups.{$correctionGroup->id}.is_active", $correctionGroup->is_active))
                                                   @disabled($season->is_locked)>

                                            <label class="form-check-label fw-bold"
                                                   for="season_correction_group_active_{{ $correctionGroup->id }}">
                                                Actif
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="fw-bold mb-2">
                                            Questions liées
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @forelse($correctionGroup->questions->sortBy('position') as $question)
                                                <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">
                                                    {{ $question->label }}
                                                </span>
                                            @empty
                                                <span class="text-muted small">
                                                    Aucune question liée.
                                                </span>
                                            @endforelse
                                        </div>

                                        <div class="border rounded-4 bg-light overflow-hidden">
                                            <button type="button"
                                                    class="btn w-100 text-start p-3 d-flex justify-content-between align-items-center fw-bold"
                                                    onclick="toggleSeasonCorrectionGroupQuestions({{ $correctionGroup->id }})">
                                                <span>
                                                    Modifier les questions liées
                                                </span>

                                                <span id="season_correction_group_questions_icon_{{ $correctionGroup->id }}">
                                                    +
                                                </span>
                                            </button>

                                            <div id="season_correction_group_questions_{{ $correctionGroup->id }}"
                                                 class="p-3 border-top d-none">
                                                <div class="row g-2">
                                                    @foreach($questions->sortBy('position') as $question)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="correction_groups[{{ $correctionGroup->id }}][question_ids][]"
                                                                       value="{{ $question->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($question->id))
                                                                       @disabled($season->is_locked)>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $question->position }}. {{ $question->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $question->scoringProfile->name ?? 'Aucun barème' }}
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @unless($season->is_locked)
                        <button class="btn btn-primary rounded-pill fw-bold px-4 mt-3">
                            Enregistrer les groupes
                        </button>
                    @endunless
                </form>

                @unless($season->is_locked)
                    @foreach($correctionGroups as $correctionGroup)
                        <form id="delete-correction-group-{{ $correctionGroup->id }}"
                              method="POST"
                              action="{{ route('admin.seasons.preseason.correction-groups.destroy', [$season, $correctionGroup]) }}"
                              class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endunless
            @endif
        </div>
    </div>

    @unless($season->is_locked)
        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Ajouter un groupe de correction
                </h3>

                <form method="POST" action="{{ route('admin.seasons.preseason.correction-groups.store', $season) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Libellé
                            </label>

                            <input type="text"
                                   name="label"
                                   value="{{ old('label') }}"
                                   class="form-control"
                                   placeholder="Demi-finalistes TOP 14"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Code
                            </label>

                            <input type="text"
                                   name="code"
                                   value="{{ old('code') }}"
                                   class="form-control"
                                   placeholder="top14_semifinalists">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                Ordre
                            </label>

                            <input type="number"
                                   name="position"
                                   value="{{ old('position', ($correctionGroups->max('position') ?? 0) + 10) }}"
                                   class="form-control text-center"
                                   required>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       class="form-check-input"
                                       id="new_correction_group_active"
                                       @checked(old('is_active', true))>

                                <label class="form-check-label fw-bold" for="new_correction_group_active">
                                    Actif
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">
                                Questions du groupe
                            </label>

                            @if($questions->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    Ajoute d’abord des questions avant de créer un groupe.
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($questions->sortBy('position') as $question)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="border rounded-3 p-2 w-100 bg-light d-flex gap-2 align-items-start">
                                                <input type="checkbox"
                                                       name="question_ids[]"
                                                       value="{{ $question->id }}"
                                                       class="form-check-input mt-1">

                                                <span>
                                                    <span class="fw-bold d-block">
                                                        {{ $question->position }}. {{ $question->label }}
                                                    </span>

                                                    <span class="text-muted small">
                                                        {{ $question->scoringProfile->name ?? 'Aucun barème' }}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <button class="btn btn-warning rounded-pill fw-bold px-4" @disabled($questions->isEmpty())>
                                Ajouter le groupe
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    <div class="col-12">
        <div class="rugby-card p-4">
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
                <div class="alert alert-warning mb-0">
                    Aucun bonus avant-saison n’est défini pour cette saison.
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.seasons.preseason.bonus.update', $season) }}"
                      id="seasonBonusRulesForm">
                    @csrf
                    @method('PUT')

                    <div id="seasonBonusRulesList" class="d-grid gap-3">
                        @foreach($bonusRules->sortBy('position') as $bonusRule)
                            @php
                                $selectedQuestionIds = collect(old("bonus_rules.{$bonusRule->id}.question_ids", $bonusRule->questions->pluck('id')->toArray()))
                                    ->map(fn ($id) => (int) $id);
                            @endphp

                            <div class="border rounded-4 p-3 p-md-4 bg-white list-group-item"
                                 data-id="{{ $bonusRule->id }}"
                                 draggable="{{ $season->is_locked ? 'false' : 'true' }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer"
                                                @disabled($season->is_locked)>
                                            ☰
                                        </button>

                                        <input type="hidden"
                                               name="bonus_rules[{{ $bonusRule->id }}][position]"
                                               value="{{ old("bonus_rules.{$bonusRule->id}.position", $bonusRule->position) }}"
                                               class="season-bonus-position-input">

                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <h4 class="h5 fw-bold mb-0">
                                                    {{ $bonusRule->label }}
                                                </h4>

                                                <span class="badge rounded-pill text-bg-warning">
                                                    +{{ $bonusRule->points }} pts
                                                </span>

                                                @if($bonusRule->is_active)
                                                    <span class="badge rounded-pill text-bg-primary">
                                                        Actif
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-secondary">
                                                        Inactif
                                                    </span>
                                                @endif

                                                @if($bonusRule->stop_after_match)
                                                    <span class="badge rounded-pill text-bg-danger">
                                                        Stop
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-success">
                                                        Cumulable
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="text-muted small">
                                                {{ $bonusRule->questions->count() }} question(s) nécessaire(s)
                                            </div>
                                        </div>
                                    </div>

                                    @unless($season->is_locked)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger rounded-pill"
                                                data-label="{{ $bonusRule->label }}"
                                                onclick="submitDeleteForm('delete-bonus-rule-{{ $bonusRule->id }}', this.dataset.label)">
                                            Supprimer
                                        </button>
                                    @endunless
                                </div>

                                <div class="row g-3">
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

                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="bonus_rules[{{ $bonusRule->id }}][is_active]"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="season_bonus_active_{{ $bonusRule->id }}"
                                                   @checked(old("bonus_rules.{$bonusRule->id}.is_active", $bonusRule->is_active))
                                                   @disabled($season->is_locked)>

                                            <label class="form-check-label fw-bold"
                                                   for="season_bonus_active_{{ $bonusRule->id }}">
                                                Actif
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="bonus_rules[{{ $bonusRule->id }}][stop_after_match]"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="season_bonus_stop_{{ $bonusRule->id }}"
                                                   @checked(old("bonus_rules.{$bonusRule->id}.stop_after_match", $bonusRule->stop_after_match))
                                                   @disabled($season->is_locked)>

                                            <label class="form-check-label fw-bold"
                                                   for="season_bonus_stop_{{ $bonusRule->id }}">
                                                Stop après réussite
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="fw-bold mb-2">
                                            Questions liées
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @forelse($bonusRule->questions->sortBy('position') as $question)
                                                <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">
                                                    {{ $question->label }}
                                                </span>
                                            @empty
                                                <span class="text-muted small">
                                                    Aucune question liée.
                                                </span>
                                            @endforelse
                                        </div>

                                        <div class="border rounded-4 bg-light overflow-hidden">
                                            <button type="button"
                                                    class="btn w-100 text-start p-3 d-flex justify-content-between align-items-center fw-bold"
                                                    onclick="toggleSeasonBonusQuestions({{ $bonusRule->id }})">
                                                <span>
                                                    Modifier les questions liées
                                                </span>

                                                <span id="season_bonus_questions_icon_{{ $bonusRule->id }}">
                                                    +
                                                </span>
                                            </button>

                                            <div id="season_bonus_questions_{{ $bonusRule->id }}"
                                                 class="p-3 border-top d-none">
                                                <div class="row g-2">
                                                    @foreach($questions->sortBy('position') as $question)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="bonus_rules[{{ $bonusRule->id }}][question_ids][]"
                                                                       value="{{ $question->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($question->id))
                                                                       @disabled($season->is_locked)>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $question->position }}. {{ $question->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $question->scoringProfile->name ?? 'Aucun barème' }}

                                                                        @if($question->correctionGroups->isNotEmpty())
                                                                            ·
                                                                            @foreach($question->correctionGroups as $group)
                                                                                <a href="#correction-group-{{ $group->id }}"
                                                                                   class="text-decoration-none correction-group-anchor-link"
                                                                                   title="Aller au groupe {{ $group->label }}">
                                                                                    {{ $group->label }}
                                                                                </a>@if(! $loop->last), @endif
                                                                            @endforeach
                                                                        @endif
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @unless($season->is_locked)
                        <button class="btn btn-primary rounded-pill fw-bold px-4 mt-3">
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
    </div>

    @unless($season->is_locked)
        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Ajouter un bonus
                </h3>

                <form method="POST" action="{{ route('admin.seasons.preseason.bonus.store', $season) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
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

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       class="form-check-input"
                                       id="new_bonus_active"
                                       @checked(old('is_active', true))>

                                <label class="form-check-label fw-bold" for="new_bonus_active">
                                    Actif
                                </label>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="stop_after_match"
                                       value="1"
                                       class="form-check-input"
                                       id="new_bonus_stop"
                                       @checked(old('stop_after_match'))>

                                <label class="form-check-label fw-bold" for="new_bonus_stop">
                                    Stop après réussite
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">
                                Questions liées
                            </label>

                            @if($questions->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    Ajoute d’abord des questions avant de lier un bonus.
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($questions->sortBy('position') as $question)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="border rounded-3 p-2 w-100 bg-light d-flex gap-2 align-items-start">
                                                <input type="checkbox"
                                                       name="question_ids[]"
                                                       value="{{ $question->id }}"
                                                       class="form-check-input mt-1">

                                                <span>
                                                    <span class="fw-bold d-block">
                                                        {{ $question->position }}. {{ $question->label }}
                                                    </span>

                                                    <span class="text-muted small">
                                                        {{ $question->scoringProfile->name ?? 'Aucun barème' }}

                                                        @if($question->correctionGroups->isNotEmpty())
                                                            ·
                                                            @foreach($question->correctionGroups as $group)
                                                                <a href="#correction-group-{{ $group->id }}"
                                                                   class="text-decoration-none correction-group-anchor-link"
                                                                   title="Aller au groupe {{ $group->label }}">
                                                                    {{ $group->label }}
                                                                </a>@if(! $loop->last), @endif
                                                            @endforeach
                                                        @endif
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <button class="btn btn-warning rounded-pill fw-bold px-4" @disabled($questions->isEmpty())>
                                Ajouter le bonus
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endunless
</div>

<button type="button"
        id="backToTopButton"
        class="btn btn-primary rounded-circle shadow position-fixed d-none"
        style="right: 1.25rem; bottom: 1.25rem; z-index: 1050; width: 3rem; height: 3rem;"
        aria-label="Retour en haut"
        title="Retour en haut">
    ↑
</button>

@unless($season->is_locked)
    <div class="modal fade" id="syncToGlobalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Appliquer aux paramètres globaux ?
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Cette action va remplacer les paramètres globaux avant-saison par la configuration de cette saison.
                    </p>

                    <p class="mb-0 text-muted">
                        Les prochaines saisons pourront repartir de ces nouveaux paramètres globaux.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <form method="POST" action="{{ route('admin.seasons.preseason.sync-to-global', $season) }}">
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

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    Confirmer la suppression
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                Supprimer définitivement :
                <strong id="deleteConfirmLabel"></strong>
                ?
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                    Annuler
                </button>

                <button type="button" class="btn btn-danger rounded-pill fw-bold" id="deleteConfirmButton">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let pendingDeleteFormId = null;

    function submitDeleteForm(formId, label) {
        const form = document.getElementById(formId);

        if (!form) {
            return;
        }

        pendingDeleteFormId = formId;

        const labelElement = document.getElementById('deleteConfirmLabel');

        if (labelElement) {
            labelElement.textContent = label || '';
        }

        const modalElement = document.getElementById('deleteConfirmModal');

        if (window.bootstrap && modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        if (confirm('Supprimer définitivement : ' + (label || 'cet élément') + ' ?')) {
            form.submit();
        }
    }

    function toggleSeasonCorrectionGroupQuestions(id) {
        togglePanel(
            'season_correction_group_questions_' + id,
            'season_correction_group_questions_icon_' + id
        );
    }

    function toggleSeasonBonusQuestions(id) {
        togglePanel(
            'season_bonus_questions_' + id,
            'season_bonus_questions_icon_' + id
        );
    }

    function togglePanel(panelId, iconId) {
        const panel = document.getElementById(panelId);
        const icon = document.getElementById(iconId);

        if (!panel) {
            return;
        }

        panel.classList.toggle('d-none');

        if (icon) {
            icon.textContent = panel.classList.contains('d-none') ? '+' : '−';
        }
    }

    function setupReorderableList(containerSelector, itemSelector, positionSelector) {
        const container = document.querySelector(containerSelector);

        if (!container) {
            return;
        }

        let draggedItem = null;

        container.addEventListener('dragstart', function (event) {
            const item = event.target.closest(itemSelector);

            if (!item || !event.target.closest('.drag-handle')) {
                event.preventDefault();
                return;
            }

            draggedItem = item;
            item.classList.add('opacity-50');
            event.dataTransfer.effectAllowed = 'move';
        });

        container.addEventListener('dragend', function () {
            if (draggedItem) {
                draggedItem.classList.remove('opacity-50');
            }

            draggedItem = null;
            refreshPositions(container, itemSelector, positionSelector);
        });

        container.addEventListener('dragover', function (event) {
            event.preventDefault();

            const target = event.target.closest(itemSelector);

            if (!draggedItem || !target || draggedItem === target) {
                return;
            }

            const rectangle = target.getBoundingClientRect();
            const next = (event.clientY - rectangle.top) / rectangle.height > 0.5;

            container.insertBefore(draggedItem, next ? target.nextSibling : target);
        });

        refreshPositions(container, itemSelector, positionSelector);
    }

    function refreshPositions(container, itemSelector, positionSelector) {
        container.querySelectorAll(itemSelector).forEach(function (item, index) {
            const positionInput = item.querySelector(positionSelector);

            if (positionInput) {
                positionInput.value = (index + 1) * 10;
            }
        });
    }

    function highlightCorrectionGroupTarget(targetId) {
        const target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        target.classList.add('border-primary', 'shadow');

        setTimeout(function () {
            target.classList.remove('border-primary', 'shadow');
        }, 1800);
    }

    function setupCorrectionGroupAnchorLinks() {
        document.querySelectorAll('.correction-group-anchor-link').forEach(function (link) {
            link.addEventListener('click', function () {
                const href = link.getAttribute('href');

                if (!href || !href.startsWith('#')) {
                    return;
                }

                const targetId = href.substring(1);

                setTimeout(function () {
                    highlightCorrectionGroupTarget(targetId);
                }, 150);
            });
        });

        if (window.location.hash && window.location.hash.startsWith('#correction-group-')) {
            setTimeout(function () {
                highlightCorrectionGroupTarget(window.location.hash.substring(1));
            }, 300);
        }
    }

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
        const deleteButton = document.getElementById('deleteConfirmButton');

        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                if (!pendingDeleteFormId) {
                    return;
                }

                const form = document.getElementById(pendingDeleteFormId);

                if (form) {
                    form.submit();
                }
            });
        }

        setupReorderableList('#seasonPreseasonQuestionsList', 'tr', '.question-position-input');
        setupReorderableList('#seasonCorrectionGroupsList', '.list-group-item', '.season-correction-group-position-input');
        setupReorderableList('#seasonBonusRulesList', '.list-group-item', '.season-bonus-position-input');

        const questionsForm = document.getElementById('seasonPreseasonQuestionsForm');

        if (questionsForm) {
            questionsForm.addEventListener('submit', function () {
                const container = document.querySelector('#seasonPreseasonQuestionsList');

                if (container) {
                    refreshPositions(container, 'tr', '.question-position-input');
                }
            });
        }

        const correctionGroupsForm = document.getElementById('seasonCorrectionGroupsForm');

        if (correctionGroupsForm) {
            correctionGroupsForm.addEventListener('submit', function () {
                const container = document.querySelector('#seasonCorrectionGroupsList');

                if (container) {
                    refreshPositions(container, '.list-group-item', '.season-correction-group-position-input');
                }
            });
        }

        const bonusRulesForm = document.getElementById('seasonBonusRulesForm');

        if (bonusRulesForm) {
            bonusRulesForm.addEventListener('submit', function () {
                const container = document.querySelector('#seasonBonusRulesList');

                if (container) {
                    refreshPositions(container, '.list-group-item', '.season-bonus-position-input');
                }
            });
        }

        setupCorrectionGroupAnchorLinks();
        setupBackToTopButton();
    });
</script>
@endpush
