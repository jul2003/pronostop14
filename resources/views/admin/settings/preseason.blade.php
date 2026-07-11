@extends('layouts.pronos')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.index') }}" class="text-decoration-none fw-bold">
        ← Retour administration
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Paramètres avant-saison
    </h2>

    <p class="text-muted mb-0">
        Gère les barèmes, les questions, les groupes de correction et les bonus utilisés pour les pronostics avant-saison.
    </p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
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
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Barèmes avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        Barèmes disponibles pour les questions avant-saison.
                    </p>
                </div>

                <a href="{{ route('admin.settings.scoring-profiles.create', ['return_to' => 'preseason', 'category' => 'preseason']) }}"
                   class="btn btn-warning rounded-pill fw-bold px-4">
                    + Créer un barème
                </a>
            </div>

            @if($profiles->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun barème avant-saison n’est encore configuré.
                </div>
            @else
                <div class="row g-3">
                    @foreach($profiles as $profile)
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-bold">
                                            {{ $profile->name }}
                                        </div>

                                        <div class="text-muted small">
                                            {{ $profile->code }}
                                        </div>
                                    </div>

                                    <a href="{{ route('admin.settings.scoring-profiles.edit', ['profile' => $profile, 'return_to' => 'preseason']) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill">
                                        Modifier
                                    </a>
                                </div>

                                @if($profile->description)
                                    <div class="text-muted small mt-2">
                                        {{ $profile->description }}
                                    </div>
                                @endif

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    @foreach($profile->rules as $rule)
                                        <span class="badge rounded-pill text-bg-light border text-dark">
                                            {{ $rule->label }} : {{ $rule->points }} pts
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Questions avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        Les questions sont affichées dans cet ordre aux joueurs. Les groupes de correction sont gérés dans le bloc séparé ci-dessous.
                    </p>
                </div>
            </div>

            <div class="border rounded-4 p-3 p-md-4 mb-4 bg-light">
                <h4 class="h6 fw-bold mb-3">
                    Ajouter une question
                </h4>

                <form method="POST" action="{{ route('admin.settings.preseason-templates.store') }}">
                    @csrf

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Libellé
                            </label>

                            <input type="text"
                                   name="label"
                                   class="form-control"
                                   placeholder="Champion TOP 14"
                                   value="{{ old('label') }}"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Type de réponse
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
                                    Club de la saison
                                </option>
                                <option value="free_text" @selected(old('answer_type') === 'free_text')>
                                    Champ libre
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Barème
                            </label>

                            <select name="scoring_profile_id"
                                    class="form-select"
                                    required>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}" @selected((string) old('scoring_profile_id') === (string) $profile->id)>
                                        {{ $profile->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                    Actif
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

            @if($preseasonTemplates->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucune question avant-saison n’est encore configurée.
                </div>
            @else
                <form method="POST" action="{{ route('admin.settings.update') }}" id="preseasonTemplatesForm">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;"></th>
                                    <th>Question</th>
                                    <th style="width: 180px;">Type</th>
                                    <th style="width: 280px;">Barème</th>
                                    <th class="text-center" style="width: 90px;">Actif</th>
                                    <th class="text-end" style="width: 110px;">Actions</th>
                                </tr>
                            </thead>

                            <tbody id="preseasonTemplatesList">
                                @foreach($preseasonTemplates as $template)
                                    <tr data-id="{{ $template->id }}" draggable="true">
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                    title="Déplacer">
                                                ↕
                                            </button>

                                            <input type="hidden"
                                                   name="preseason[{{ $template->id }}][position]"
                                                   value="{{ old("preseason.{$template->id}.position", $template->position) }}"
                                                   class="template-position-input">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="preseason[{{ $template->id }}][label]"
                                                   value="{{ old("preseason.{$template->id}.label", $template->label) }}"
                                                   class="form-control"
                                                   required>

                                            @if($template->correctionGroups->isNotEmpty())
                                                <div class="small text-muted mt-2 d-flex flex-wrap gap-1">
                                                    @foreach($template->correctionGroups as $correctionGroup)
                                                        <span class="badge rounded-pill text-bg-light border text-dark">
                                                            {{ $correctionGroup->label }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][answer_type]"
                                                    class="form-select"
                                                    required>
                                                <option value="top14_club" @selected(old("preseason.{$template->id}.answer_type", $template->answer_type) === 'top14_club')>
                                                    Club TOP 14
                                                </option>
                                                <option value="prod2_club" @selected(old("preseason.{$template->id}.answer_type", $template->answer_type) === 'prod2_club')>
                                                    Club PRO D2
                                                </option>
                                                <option value="season_club" @selected(old("preseason.{$template->id}.answer_type", $template->answer_type) === 'season_club')>
                                                    Club saison
                                                </option>
                                                <option value="free_text" @selected(old("preseason.{$template->id}.answer_type", $template->answer_type) === 'free_text')>
                                                    Champ libre
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][scoring_profile_id]"
                                                    class="form-select"
                                                    required>
                                                @foreach($profiles as $profile)
                                                    <option value="{{ $profile->id }}" @selected((string) old("preseason.{$template->id}.scoring_profile_id", $template->scoring_profile_id) === (string) $profile->id)>
                                                        {{ $profile->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="preseason[{{ $template->id }}][is_active]"
                                                   value="1"
                                                   class="form-check-input"
                                                   @checked(old("preseason.{$template->id}.is_active", $template->is_active))>
                                        </td>

                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="submitDeleteForm('delete-template-{{ $template->id }}', '{{ addslashes($template->label) }}')">
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button class="btn btn-primary rounded-pill fw-bold px-4 mt-3">
                        Enregistrer les questions
                    </button>
                </form>

                @foreach($preseasonTemplates as $template)
                    <form id="delete-template-{{ $template->id }}"
                          method="POST"
                          action="{{ route('admin.settings.preseason-templates.destroy', $template) }}"
                          class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Groupes de correction
                    </h3>

                    <p class="text-muted mb-0">
                        Un groupe corrige plusieurs questions ensemble, sans tenir compte de l’ordre des réponses. Exemple : les quatre demi-finalistes TOP 14.
                    </p>
                </div>
            </div>

            <div class="border rounded-4 p-3 p-md-4 mb-4 bg-light">
                <h4 class="h6 fw-bold mb-3">
                    Ajouter un groupe de correction
                </h4>

                <form method="POST" action="{{ route('admin.settings.preseason-correction-groups.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Libellé
                            </label>

                            <input type="text"
                                   name="label"
                                   class="form-control"
                                   placeholder="Demi-finalistes TOP 14"
                                   value="{{ old('label') }}"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Code
                            </label>

                            <input type="text"
                                   name="code"
                                   class="form-control"
                                   placeholder="top14_semifinalists"
                                   value="{{ old('code') }}">
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

                            @if($preseasonTemplates->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    Ajoute d’abord des questions avant de créer un groupe de correction.
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($preseasonTemplates as $template)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                <input type="checkbox"
                                                       name="questions[]"
                                                       value="{{ $template->id }}"
                                                       class="form-check-input mt-1">

                                                <span>
                                                    <span class="fw-bold d-block">
                                                        {{ $template->label }}
                                                    </span>

                                                    <span class="text-muted small">
                                                        {{ $template->profile->name ?? 'Aucun barème' }}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="col-12">
                            <button class="btn btn-warning rounded-pill fw-bold px-4" @disabled($preseasonTemplates->isEmpty())>
                                Créer le groupe
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($preseasonCorrectionGroups->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun groupe de correction avant-saison n’est encore configuré.
                </div>
            @else
                <div id="preseasonCorrectionGroupsList" class="d-grid gap-3">
                    @foreach($preseasonCorrectionGroups as $correctionGroup)
                        @php
                            $selectedQuestionIds = collect(old("correction_groups.{$correctionGroup->id}.questions", $correctionGroup->questions->pluck('id')->toArray()))
                                ->map(fn ($id) => (int) $id);
                        @endphp

                        <div class="border rounded-4 p-3 p-md-4 bg-white list-group-item"
                             data-id="{{ $correctionGroup->id }}"
                             draggable="true">
                            <form method="POST" action="{{ route('admin.settings.preseason-correction-groups.update', $correctionGroup) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="position"
                                       value="{{ old("correction_groups.{$correctionGroup->id}.position", $correctionGroup->position) }}"
                                       class="correction-group-position-input">

                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer">
                                            ↕
                                        </button>

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

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="submitDeleteForm('delete-correction-group-{{ $correctionGroup->id }}', '{{ addslashes($correctionGroup->label) }}')">
                                        Supprimer
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">
                                            Libellé
                                        </label>

                                        <input type="text"
                                               name="label"
                                               value="{{ old("correction_groups.{$correctionGroup->id}.label", $correctionGroup->label) }}"
                                               class="form-control"
                                               required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">
                                            Code
                                        </label>

                                        <input type="text"
                                               name="code"
                                               value="{{ old("correction_groups.{$correctionGroup->id}.code", $correctionGroup->code) }}"
                                               class="form-control">
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="correction_group_active_{{ $correctionGroup->id }}"
                                                   @checked(old("correction_groups.{$correctionGroup->id}.is_active", $correctionGroup->is_active))>

                                            <label class="form-check-label fw-bold"
                                                   for="correction_group_active_{{ $correctionGroup->id }}">
                                                Actif
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="fw-bold mb-2">
                                            Questions liées
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @forelse($correctionGroup->questions as $question)
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
                                                    onclick="toggleCorrectionGroupQuestions({{ $correctionGroup->id }})">
                                                <span>
                                                    Modifier les questions liées
                                                </span>

                                                <span id="correction_group_questions_icon_{{ $correctionGroup->id }}">
                                                    +
                                                </span>
                                            </button>

                                            <div id="correction_group_questions_{{ $correctionGroup->id }}"
                                                 class="p-3 border-top d-none">
                                                <div class="row g-2">
                                                    @foreach($preseasonTemplates as $template)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="questions[]"
                                                                       value="{{ $template->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($template->id))>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $template->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $template->profile->name ?? 'Aucun barème' }}
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary rounded-pill fw-bold px-4">
                                            Enregistrer ce groupe
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form id="delete-correction-group-{{ $correctionGroup->id }}"
                                  method="POST"
                                  action="{{ route('admin.settings.preseason-correction-groups.destroy', $correctionGroup) }}"
                                  class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Bonus avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        Bonus accordés quand plusieurs pronostics avant-saison sont corrects. L’ordre est important : les bonus sont évalués de haut en bas.
                    </p>
                </div>
            </div>

            <div class="border rounded-4 p-3 p-md-4 mb-4 bg-light">
                <h4 class="h6 fw-bold mb-3">
                    Ajouter un bonus
                </h4>

                <form method="POST" action="{{ route('admin.settings.preseason-bonus-rules.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">
                                Libellé
                            </label>

                            <input type="text"
                                   name="label"
                                   class="form-control"
                                   placeholder="Bonus demi-finalistes TOP 14"
                                   value="{{ old('label') }}"
                                   required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                Points
                            </label>

                            <input type="number"
                                   name="points"
                                   class="form-control text-center"
                                   value="{{ old('points', 0) }}"
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

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="stop_after_match"
                                       value="1"
                                       class="form-check-input"
                                       id="new_bonus_stop"
                                       @checked(old('stop_after_match'))>

                                <label class="form-check-label fw-bold" for="new_bonus_stop">
                                    Stop après obtention
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">
                                Questions nécessaires
                            </label>

                            @if($preseasonTemplates->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    Ajoute d’abord des questions avant de créer un bonus.
                                </div>
                            @else
                                <div class="row g-2">
                                    @foreach($preseasonTemplates as $template)
                                        <div class="col-md-6 col-xl-4">
                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                <input type="checkbox"
                                                       name="questions[]"
                                                       value="{{ $template->id }}"
                                                       class="form-check-input mt-1">

                                                <span>
                                                    <span class="fw-bold d-block">
                                                        {{ $template->label }}
                                                    </span>

                                                    <span class="text-muted small">
                                                        {{ $template->profile->name ?? 'Aucun barème' }}

                                                        @if($template->correctionGroups->isNotEmpty())
                                                            · {{ $template->correctionGroups->pluck('label')->join(', ') }}
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
                            <button class="btn btn-warning rounded-pill fw-bold px-4" @disabled($preseasonTemplates->isEmpty())>
                                Enregistrer le bonus
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($preseasonBonusRules->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun bonus avant-saison n’est encore configuré.
                </div>
            @else
                <div id="preseasonBonusRulesList" class="d-grid gap-3">
                    @foreach($preseasonBonusRules as $bonusRule)
                        @php
                            $selectedQuestionIds = collect(old("bonus_rules.{$bonusRule->id}.questions", $bonusRule->questions->pluck('id')->toArray()))
                                ->map(fn ($id) => (int) $id);
                        @endphp

                        <div class="border rounded-4 p-3 p-md-4 bg-white list-group-item"
                             data-id="{{ $bonusRule->id }}"
                             draggable="true">
                            <form method="POST"
                                  action="{{ route('admin.settings.preseason-bonus-rules.update', $bonusRule) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="position"
                                       value="{{ old("bonus_rules.{$bonusRule->id}.position", $bonusRule->position) }}"
                                       class="bonus-position-input">

                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer">
                                            ↕
                                        </button>

                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <h4 class="h5 fw-bold mb-0">
                                                    {{ $bonusRule->label }}
                                                </h4>

                                                <span class="badge rounded-pill text-bg-warning">
                                                    +{{ $bonusRule->points }} pts
                                                </span>

                                                @if($bonusRule->stop_after_match)
                                                    <span class="badge rounded-pill text-bg-danger">
                                                        Stop
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-success">
                                                        Cumulable
                                                    </span>
                                                @endif

                                                @if($bonusRule->is_active)
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
                                                {{ $bonusRule->questions->count() }} question(s) nécessaire(s)
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="submitDeleteForm('delete-bonus-rule-{{ $bonusRule->id }}', '{{ addslashes($bonusRule->label) }}')">
                                        Supprimer
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">
                                            Libellé
                                        </label>

                                        <input type="text"
                                               name="label"
                                               value="{{ old("bonus_rules.{$bonusRule->id}.label", $bonusRule->label) }}"
                                               class="form-control"
                                               required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">
                                            Points
                                        </label>

                                        <input type="number"
                                               name="points"
                                               value="{{ old("bonus_rules.{$bonusRule->id}.points", $bonusRule->points) }}"
                                               class="form-control text-center"
                                               required>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="bonus_active_{{ $bonusRule->id }}"
                                                   @checked(old("bonus_rules.{$bonusRule->id}.is_active", $bonusRule->is_active))>

                                            <label class="form-check-label fw-bold"
                                                   for="bonus_active_{{ $bonusRule->id }}">
                                                Actif
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="stop_after_match"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="bonus_stop_{{ $bonusRule->id }}"
                                                   @checked(old("bonus_rules.{$bonusRule->id}.stop_after_match", $bonusRule->stop_after_match))>

                                            <label class="form-check-label fw-bold"
                                                   for="bonus_stop_{{ $bonusRule->id }}">
                                                Stop après obtention
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="fw-bold mb-2">
                                            Questions liées
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            @forelse($bonusRule->questions as $question)
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
                                                    onclick="toggleBonusQuestions({{ $bonusRule->id }})">
                                                <span>
                                                    Modifier les questions liées
                                                </span>

                                                <span id="bonus_questions_icon_{{ $bonusRule->id }}">
                                                    +
                                                </span>
                                            </button>

                                            <div id="bonus_questions_{{ $bonusRule->id }}"
                                                 class="p-3 border-top d-none">
                                                <div class="row g-2">
                                                    @foreach($preseasonTemplates as $template)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="questions[]"
                                                                       value="{{ $template->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($template->id))>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $template->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $template->profile->name ?? 'Aucun barème' }}

                                                                        @if($template->correctionGroups->isNotEmpty())
                                                                            · {{ $template->correctionGroups->pluck('label')->join(', ') }}
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

                                    <div class="col-12">
                                        <button class="btn btn-primary rounded-pill fw-bold px-4">
                                            Enregistrer ce bonus
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form id="delete-bonus-rule-{{ $bonusRule->id }}"
                                  method="POST"
                                  action="{{ route('admin.settings.preseason-bonus-rules.destroy', $bonusRule) }}"
                                  class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

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

    function toggleBonusQuestions(id) {
        togglePanel('bonus_questions_' + id, 'bonus_questions_icon_' + id);
    }

    function toggleCorrectionGroupQuestions(id) {
        togglePanel('correction_group_questions_' + id, 'correction_group_questions_icon_' + id);
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

        setupReorderableList('#preseasonTemplatesList', 'tr', '.template-position-input');
        setupReorderableList('#preseasonCorrectionGroupsList', '.list-group-item', '.correction-group-position-input');
        setupReorderableList('#preseasonBonusRulesList', '.list-group-item', '.bonus-position-input');

        const questionsForm = document.getElementById('preseasonTemplatesForm');

        if (questionsForm) {
            questionsForm.addEventListener('submit', function () {
                const container = document.querySelector('#preseasonTemplatesList');

                if (container) {
                    refreshPositions(container, 'tr', '.template-position-input');
                }
            });
        }
    });
</script>
@endpush
