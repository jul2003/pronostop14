@extends('layouts.pronos')

@section('content')

@php
    $answerTypeOptions = [
        'top14_club' => 'TOP 14',
        'prod2_club' => 'PRO D2',
        'season_club' => 'Saison',
        'free_text' => 'Libre',
    ];

    $answerTypeFullLabels = [
        'top14_club' => 'Club TOP 14',
        'prod2_club' => 'Club PRO D2',
        'season_club' => 'Club de la saison',
        'free_text' => 'Texte libre',
    ];

    $autoResultRuleOptions = $autoResultRuleOptions
        ?? \App\Models\SeasonPreseasonQuestion::autoResultRuleOptions();

    $autoResultRuleShortLabels = [
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_POSITION => 'Position TOP 14',
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_PLAYOFF_1_WINNER => 'Vainq. barrage 1',
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_PLAYOFF_2_WINNER => 'Vainq. barrage 2',
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_TOP14_FINAL_WINNER => 'Vainq. finale T14',
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_PROD2_FINAL_WINNER => 'Vainq. finale D2',
        \App\Models\SeasonPreseasonQuestion::AUTO_RESULT_RULE_ACCESS_MATCH_WINNER => 'Vainq. access',
    ];

    $autoResultRuleMetadata = collect(array_keys($autoResultRuleOptions))
        ->mapWithKeys(fn ($rule) => [
            $rule => [
                'compatible_answer_types' => \App\Models\SeasonPreseasonQuestion::autoResultRuleCompatibleAnswerTypes($rule),
                'requires_journee' => \App\Models\SeasonPreseasonQuestion::autoResultRuleRequiresJourneeNumber($rule),
                'requires_position' => \App\Models\SeasonPreseasonQuestion::autoResultRuleRequiresPosition($rule),
            ],
        ]);

    $profilePoints = $profiles->mapWithKeys(function ($profile) {
        $correctRule = $profile->rules->firstWhere('code', 'correct')
            ?? $profile->rules->first();

        return [
            (int) $profile->id => $correctRule?->points ?? 0,
        ];
    });

    $defaultScoringProfileId = old(
        'scoring_profile_id',
        $profiles->first()?->id
    );

    $oldFormContext = old('form_context');
@endphp

@include('admin.partials.back-link', [
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div id="page-top" class="mb-4">
    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Paramètres avant-saison
    </h2>

    <p class="text-muted mb-0">
        Modèle global utilisé pour préparer les questions, groupes de correction,
        bonus et calculs automatiques des prochaines saisons.
    </p>
</div>

<div class="alert alert-info">
    <div class="fw-bold mb-1">
        Configuration globale
    </div>

    Les modifications faites ici servent de modèle aux prochaines saisons.
    Le bouton <strong>Appliquer aux paramètres globaux</strong> présent sur une saison
    remplace cette configuration par celle de la saison concernée, y compris les règles
    de calcul automatique.
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Questions avant-saison
                    </h3>

                    <p class="text-muted mb-0">
                        Modifie, supprime ou réordonne les questions. Les règles automatiques
                        sont visibles directement dans le tableau, comme sur la page d’une saison.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                    {{ $preseasonTemplates->count() }} question(s)
                </span>
            </div>

            @if($preseasonTemplates->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucune question avant-saison n’est encore configurée.
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.settings.update') }}"
                      id="preseasonTemplatesForm">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 preseason-questions-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 48px;"></th>
                                    <th class="question-column">
                                        Question
                                    </th>
                                    <th class="text-center">
                                        Type
                                    </th>
                                    <th class="auto-column">
                                        Auto
                                    </th>
                                    <th class="text-center">
                                        J
                                    </th>
                                    <th class="text-center">
                                        Pos.
                                    </th>
                                    <th class="profile-column">
                                        Barème
                                    </th>
                                    <th class="text-center">
                                        Actif
                                    </th>
                                    <th class="text-end">
                                        Supp.
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="preseasonTemplatesList">
                                @foreach($preseasonTemplates->sortBy('position') as $template)
                                    @php
                                        $templateType = old(
                                            "preseason.{$template->id}.answer_type",
                                            $template->answer_type
                                        );

                                        $templateAutoRule = old(
                                            "preseason.{$template->id}.auto_result_rule",
                                            $template->auto_result_rule
                                        );

                                        $templateAutoJournee = old(
                                            "preseason.{$template->id}.auto_result_journee_number",
                                            $template->auto_result_journee_number
                                        );

                                        $templateAutoPosition = old(
                                            "preseason.{$template->id}.auto_result_position",
                                            $template->auto_result_position
                                        );
                                    @endphp

                                    <tr data-id="{{ $template->id }}"
                                        draggable="true">
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-light border drag-handle rounded-pill px-2"
                                                    title="Déplacer">
                                                ☰
                                            </button>

                                            <input type="hidden"
                                                   name="preseason[{{ $template->id }}][position]"
                                                   value="{{ old("preseason.{$template->id}.position", $template->position) }}"
                                                   class="template-position-input">
                                        </td>

                                        <td class="question-column">
                                            <input type="text"
                                                   name="preseason[{{ $template->id }}][label]"
                                                   value="{{ old("preseason.{$template->id}.label", $template->label) }}"
                                                   class="form-control form-control-sm question-label-input"
                                                   required>

                                            @if($template->correctionGroups->isNotEmpty())
                                                <div class="small text-muted mt-1 d-flex flex-wrap gap-1">
                                                    @foreach($template->correctionGroups as $correctionGroup)
                                                        <a href="#global-correction-group-{{ $correctionGroup->id }}"
                                                           class="badge rounded-pill text-bg-light border text-dark text-decoration-none correction-group-anchor-link"
                                                           title="Aller au groupe {{ $correctionGroup->label }}">
                                                            {{ $correctionGroup->label }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][answer_type]"
                                                    class="form-select form-select-sm js-question-answer-type"
                                                    data-question-id="{{ $template->id }}"
                                                    required>
                                                @foreach($answerTypeOptions as $value => $label)
                                                    <option value="{{ $value }}"
                                                            title="{{ $answerTypeFullLabels[$value] ?? $label }}"
                                                            @selected($templateType === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="auto-column">
                                            <select name="preseason[{{ $template->id }}][auto_result_rule]"
                                                    class="form-select form-select-sm js-auto-result-rule"
                                                    data-question-id="{{ $template->id }}">
                                                <option value="">
                                                    Aucun
                                                </option>

                                                @foreach($autoResultRuleOptions as $value => $label)
                                                    @php
                                                        $metadata = $autoResultRuleMetadata[$value] ?? [
                                                            'compatible_answer_types' => [],
                                                            'requires_journee' => false,
                                                            'requires_position' => false,
                                                        ];

                                                        $shortLabel = $autoResultRuleShortLabels[$value] ?? $label;
                                                    @endphp

                                                    <option value="{{ $value }}"
                                                            title="{{ $label }}"
                                                            data-compatible-answer-types="{{ implode(',', $metadata['compatible_answer_types']) }}"
                                                            data-requires-journee="{{ $metadata['requires_journee'] ? '1' : '0' }}"
                                                            data-requires-position="{{ $metadata['requires_position'] ? '1' : '0' }}"
                                                            @selected($templateAutoRule === $value)>
                                                        {{ $shortLabel }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <div class="small text-muted compact-help js-auto-result-help"
                                                 data-question-id="{{ $template->id }}">
                                                —
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <select name="preseason[{{ $template->id }}][auto_result_journee_number]"
                                                    class="form-select form-select-sm text-center auto-journee-select js-auto-result-journee"
                                                    data-question-id="{{ $template->id }}">
                                                <option value="">
                                                    —
                                                </option>

                                                @for($number = 1; $number <= 26; $number++)
                                                    <option value="{{ $number }}"
                                                            @selected((string) $templateAutoJournee === (string) $number)>
                                                        J{{ $number }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </td>

                                        <td class="text-center">
                                            <select name="preseason[{{ $template->id }}][auto_result_position]"
                                                    class="form-select form-select-sm text-center auto-position-select js-auto-result-position"
                                                    data-question-id="{{ $template->id }}"
                                                    title="1 = premier, 13 = barragiste, 14 = dernier">
                                                <option value="">
                                                    —
                                                </option>

                                                @for($position = 1; $position <= 14; $position++)
                                                    <option value="{{ $position }}"
                                                            @selected((string) $templateAutoPosition === (string) $position)>
                                                        {{ $position }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </td>

                                        <td class="profile-column">
                                            <select name="preseason[{{ $template->id }}][scoring_profile_id]"
                                                    class="form-select form-select-sm"
                                                    required>
                                                @foreach($profiles as $profile)
                                                    <option value="{{ $profile->id }}"
                                                            @selected((string) old("preseason.{$template->id}.scoring_profile_id", $template->scoring_profile_id) === (string) $profile->id)>
                                                        {{ $profile->name }} · {{ $profilePoints[$profile->id] ?? 0 }} pt(s)
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
                                                    class="btn btn-sm btn-outline-danger rounded-circle delete-icon-button"
                                                    data-label="{{ $template->label }}"
                                                    title="Supprimer la question"
                                                    aria-label="Supprimer la question {{ $template->label }}"
                                                    onclick="submitDeleteForm('delete-template-{{ $template->id }}', this.dataset.label)">
                                                ×
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
            <h3 class="h5 fw-bold mb-1">
                Ajouter une question
            </h3>

            <p class="text-muted mb-3">
                La nouvelle question sera ajoutée au modèle global et pourra être copiée dans les prochaines saisons.
            </p>

            <form method="POST"
                  action="{{ route('admin.settings.preseason-templates.store') }}"
                  id="newGlobalPreseasonQuestionForm">
                @csrf

                <input type="hidden"
                       name="form_context"
                       value="new_question">

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 preseason-questions-table">
                        <thead class="table-light">
                            <tr>
                                <th class="question-column">
                                    Question
                                </th>
                                <th class="text-center">
                                    Type
                                </th>
                                <th class="auto-column">
                                    Auto
                                </th>
                                <th class="text-center">
                                    J
                                </th>
                                <th class="text-center">
                                    Pos.
                                </th>
                                <th class="profile-column">
                                    Barème
                                </th>
                                <th class="text-center">
                                    Ordre
                                </th>
                                <th class="text-center">
                                    Actif
                                </th>
                                <th class="text-end">
                                    Ajouter
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="question-column">
                                    <input type="text"
                                           name="label"
                                           value="{{ $oldFormContext === 'new_question' ? old('label') : '' }}"
                                           class="form-control form-control-sm question-label-input"
                                           placeholder="Champion TOP 14"
                                           required>
                                </td>

                                <td>
                                    <select name="answer_type"
                                            class="form-select form-select-sm js-new-question-answer-type"
                                            required>
                                        @foreach($answerTypeOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                    title="{{ $answerTypeFullLabels[$value] ?? $label }}"
                                                    @selected(($oldFormContext === 'new_question' ? old('answer_type', 'top14_club') : 'top14_club') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="auto-column">
                                    <select name="auto_result_rule"
                                            class="form-select form-select-sm js-new-auto-result-rule">
                                        <option value="">
                                            Aucun
                                        </option>

                                        @foreach($autoResultRuleOptions as $value => $label)
                                            @php
                                                $metadata = $autoResultRuleMetadata[$value] ?? [
                                                    'compatible_answer_types' => [],
                                                    'requires_journee' => false,
                                                    'requires_position' => false,
                                                ];

                                                $shortLabel = $autoResultRuleShortLabels[$value] ?? $label;
                                            @endphp

                                            <option value="{{ $value }}"
                                                    title="{{ $label }}"
                                                    data-compatible-answer-types="{{ implode(',', $metadata['compatible_answer_types']) }}"
                                                    data-requires-journee="{{ $metadata['requires_journee'] ? '1' : '0' }}"
                                                    data-requires-position="{{ $metadata['requires_position'] ? '1' : '0' }}"
                                                    @selected($oldFormContext === 'new_question' && old('auto_result_rule') === $value)>
                                                {{ $shortLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="text-center">
                                    <select name="auto_result_journee_number"
                                            class="form-select form-select-sm text-center auto-journee-select js-new-auto-result-journee">
                                        <option value="">
                                            —
                                        </option>

                                        @for($number = 1; $number <= 26; $number++)
                                            <option value="{{ $number }}"
                                                    @selected($oldFormContext === 'new_question' && (string) old('auto_result_journee_number') === (string) $number)>
                                                J{{ $number }}
                                            </option>
                                        @endfor
                                    </select>
                                </td>

                                <td class="text-center">
                                    <select name="auto_result_position"
                                            class="form-select form-select-sm text-center auto-position-select js-new-auto-result-position"
                                            title="1 = premier, 13 = barragiste, 14 = dernier">
                                        <option value="">
                                            —
                                        </option>

                                        @for($position = 1; $position <= 14; $position++)
                                            <option value="{{ $position }}"
                                                    @selected($oldFormContext === 'new_question' && (string) old('auto_result_position') === (string) $position)>
                                                {{ $position }}
                                            </option>
                                        @endfor
                                    </select>
                                </td>

                                <td class="profile-column">
                                    <select name="scoring_profile_id"
                                            class="form-select form-select-sm"
                                            required>
                                        @foreach($profiles as $profile)
                                            <option value="{{ $profile->id }}"
                                                    @selected((string) ($oldFormContext === 'new_question' ? old('scoring_profile_id', $defaultScoringProfileId) : $defaultScoringProfileId) === (string) $profile->id)>
                                                {{ $profile->name }} · {{ $profilePoints[$profile->id] ?? 0 }} pt(s)
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="text-center">
                                    <input type="number"
                                           name="position"
                                           value="{{ $oldFormContext === 'new_question' ? old('position', ($preseasonTemplates->max('position') ?? 0) + 10) : (($preseasonTemplates->max('position') ?? 0) + 10) }}"
                                           class="form-control form-control-sm text-center position-input"
                                           min="0"
                                           required>
                                </td>

                                <td class="text-center">
                                    <input type="checkbox"
                                           name="is_active"
                                           value="1"
                                           class="form-check-input"
                                           id="new_question_active"
                                           @checked($oldFormContext === 'new_question' ? old('is_active', true) : true)>
                                </td>

                                <td class="text-end">
                                    <button class="btn btn-sm btn-warning rounded-pill fw-bold px-3">
                                        +
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small text-muted mt-3">
                    Pour <strong>Position TOP 14</strong>, la journée cible et la position sont obligatoires.
                    Les autres règles se déclenchent à partir du résultat du match concerné.
                </div>
            </form>
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
                        Coche les questions qui doivent être corrigées ensemble, sans tenir compte de l’ordre des réponses.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                    {{ $preseasonCorrectionGroups->count() }} groupe(s)
                </span>
            </div>

            @if($preseasonCorrectionGroups->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun groupe de correction avant-saison n’est encore configuré.
                </div>
            @else
                <div id="preseasonCorrectionGroupsList"
                     class="d-grid gap-3">
                    @foreach($preseasonCorrectionGroups->sortBy('position') as $correctionGroup)
                        @php
                            $groupContext = 'correction_group_'.$correctionGroup->id;
                            $useGroupOldValues = $oldFormContext === $groupContext;

                            $selectedQuestionIds = collect(
                                $useGroupOldValues
                                    ? old('questions', [])
                                    : $correctionGroup->questions->pluck('id')->toArray()
                            )->map(fn ($id) => (int) $id);
                        @endphp

                        <div id="global-correction-group-{{ $correctionGroup->id }}"
                             class="border rounded-4 p-3 p-md-4 bg-white list-group-item correction-group-target"
                             style="scroll-margin-top: 1.5rem;"
                             data-id="{{ $correctionGroup->id }}"
                             draggable="true">
                            <form method="POST"
                                  action="{{ route('admin.settings.preseason-correction-groups.update', $correctionGroup) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="form_context"
                                       value="{{ $groupContext }}">

                                <input type="hidden"
                                       name="position"
                                       value="{{ $useGroupOldValues ? old('position', $correctionGroup->position) : $correctionGroup->position }}"
                                       class="correction-group-position-input">

                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer">
                                            ☰
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
                                            data-label="{{ $correctionGroup->label }}"
                                            onclick="submitDeleteForm('delete-correction-group-{{ $correctionGroup->id }}', this.dataset.label)">
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
                                               value="{{ $useGroupOldValues ? old('label', $correctionGroup->label) : $correctionGroup->label }}"
                                               class="form-control"
                                               required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">
                                            Code
                                        </label>

                                        <input type="text"
                                               name="code"
                                               value="{{ $useGroupOldValues ? old('code', $correctionGroup->code) : $correctionGroup->code }}"
                                               class="form-control">
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="correction_group_active_{{ $correctionGroup->id }}"
                                                   @checked($useGroupOldValues ? old('is_active') : $correctionGroup->is_active)>

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
                                                    @foreach($preseasonTemplates->sortBy('position') as $template)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="questions[]"
                                                                       value="{{ $template->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($template->id))>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $template->position }}. {{ $template->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $template->profile->name ?? 'Aucun barème' }}
                                                                        · {{ $profilePoints[$template->scoring_profile_id] ?? 0 }} pt(s)
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
            <h3 class="h5 fw-bold mb-1">
                Ajouter un groupe de correction
            </h3>

            <p class="text-muted mb-3">
                Regroupe plusieurs questions équivalentes, par exemple les quatre demi-finalistes du TOP 14.
            </p>

            <form method="POST"
                  action="{{ route('admin.settings.preseason-correction-groups.store') }}">
                @csrf

                <input type="hidden"
                       name="form_context"
                       value="new_correction_group">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">
                            Libellé
                        </label>

                        <input type="text"
                               name="label"
                               class="form-control"
                               placeholder="Demi-finalistes TOP 14"
                               value="{{ $oldFormContext === 'new_correction_group' ? old('label') : '' }}"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Code
                        </label>

                        <input type="text"
                               name="code"
                               class="form-control"
                               placeholder="top14_semifinalists"
                               value="{{ $oldFormContext === 'new_correction_group' ? old('code') : '' }}">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   class="form-check-input"
                                   id="new_correction_group_active"
                                   @checked($oldFormContext === 'new_correction_group' ? old('is_active', true) : true)>

                            <label class="form-check-label fw-bold"
                                   for="new_correction_group_active">
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
                            @php
                                $newGroupQuestionIds = collect(
                                    $oldFormContext === 'new_correction_group'
                                        ? old('questions', [])
                                        : []
                                )->map(fn ($id) => (int) $id);
                            @endphp

                            <div class="row g-2">
                                @foreach($preseasonTemplates->sortBy('position') as $template)
                                    <div class="col-md-6 col-xl-4">
                                        <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                            <input type="checkbox"
                                                   name="questions[]"
                                                   value="{{ $template->id }}"
                                                   class="form-check-input mt-1"
                                                   @checked($newGroupQuestionIds->contains($template->id))>

                                            <span>
                                                <span class="fw-bold d-block">
                                                    {{ $template->position }}. {{ $template->label }}
                                                </span>

                                                <span class="text-muted small">
                                                    {{ $template->profile->name ?? 'Aucun barème' }}
                                                    · {{ $profilePoints[$template->scoring_profile_id] ?? 0 }} pt(s)
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="col-12">
                        <button class="btn btn-warning rounded-pill fw-bold px-4"
                                @disabled($preseasonTemplates->isEmpty())>
                            Ajouter le groupe
                        </button>
                    </div>
                </div>
            </form>
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
                        Modifie les bonus et les questions nécessaires pour les obtenir.
                        L’ordre est important : les bonus sont évalués de haut en bas.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                    {{ $preseasonBonusRules->count() }} bonus
                </span>
            </div>

            @if($preseasonBonusRules->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun bonus avant-saison n’est encore configuré.
                </div>
            @else
                <div id="preseasonBonusRulesList"
                     class="d-grid gap-3">
                    @foreach($preseasonBonusRules->sortBy('position') as $bonusRule)
                        @php
                            $bonusContext = 'bonus_'.$bonusRule->id;
                            $useBonusOldValues = $oldFormContext === $bonusContext;

                            $selectedQuestionIds = collect(
                                $useBonusOldValues
                                    ? old('questions', [])
                                    : $bonusRule->questions->pluck('id')->toArray()
                            )->map(fn ($id) => (int) $id);
                        @endphp

                        <div class="border rounded-4 p-3 p-md-4 bg-white list-group-item"
                             data-id="{{ $bonusRule->id }}"
                             draggable="true">
                            <form method="POST"
                                  action="{{ route('admin.settings.preseason-bonus-rules.update', $bonusRule) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="form_context"
                                       value="{{ $bonusContext }}">

                                <input type="hidden"
                                       name="position"
                                       value="{{ $useBonusOldValues ? old('position', $bonusRule->position) : $bonusRule->position }}"
                                       class="bonus-position-input">

                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <button type="button"
                                                class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                title="Déplacer">
                                            ☰
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
                                            data-label="{{ $bonusRule->label }}"
                                            onclick="submitDeleteForm('delete-bonus-rule-{{ $bonusRule->id }}', this.dataset.label)">
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
                                               value="{{ $useBonusOldValues ? old('label', $bonusRule->label) : $bonusRule->label }}"
                                               class="form-control"
                                               required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">
                                            Points
                                        </label>

                                        <input type="number"
                                               name="points"
                                               value="{{ $useBonusOldValues ? old('points', $bonusRule->points) : $bonusRule->points }}"
                                               class="form-control text-center"
                                               min="0"
                                               required>
                                    </div>

                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox"
                                                   name="is_active"
                                                   value="1"
                                                   class="form-check-input"
                                                   id="bonus_active_{{ $bonusRule->id }}"
                                                   @checked($useBonusOldValues ? old('is_active') : $bonusRule->is_active)>

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
                                                   @checked($useBonusOldValues ? old('stop_after_match') : $bonusRule->stop_after_match)>

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
                                                    @foreach($preseasonTemplates->sortBy('position') as $template)
                                                        <div class="col-md-6 col-xl-4">
                                                            <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                                                <input type="checkbox"
                                                                       name="questions[]"
                                                                       value="{{ $template->id }}"
                                                                       class="form-check-input mt-1"
                                                                       @checked($selectedQuestionIds->contains($template->id))>

                                                                <span>
                                                                    <span class="fw-bold d-block">
                                                                        {{ $template->position }}. {{ $template->label }}
                                                                    </span>

                                                                    <span class="text-muted small">
                                                                        {{ $template->profile->name ?? 'Aucun barème' }}

                                                                        @if($template->correctionGroups->isNotEmpty())
                                                                            ·
                                                                            @foreach($template->correctionGroups as $group)
                                                                                <a href="#global-correction-group-{{ $group->id }}"
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

    <div class="col-12">
        <div class="rugby-card p-4">
            <h3 class="h5 fw-bold mb-1">
                Ajouter un bonus
            </h3>

            <p class="text-muted mb-3">
                Le bonus sera évalué avec les autres bonus globaux, dans l’ordre défini ci-dessus.
            </p>

            <form method="POST"
                  action="{{ route('admin.settings.preseason-bonus-rules.store') }}">
                @csrf

                <input type="hidden"
                       name="form_context"
                       value="new_bonus">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">
                            Libellé
                        </label>

                        <input type="text"
                               name="label"
                               class="form-control"
                               placeholder="Bonus demi-finalistes TOP 14"
                               value="{{ $oldFormContext === 'new_bonus' ? old('label') : '' }}"
                               required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            Points
                        </label>

                        <input type="number"
                               name="points"
                               class="form-control text-center"
                               value="{{ $oldFormContext === 'new_bonus' ? old('points', 0) : 0 }}"
                               min="0"
                               required>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   class="form-check-input"
                                   id="new_bonus_active"
                                   @checked($oldFormContext === 'new_bonus' ? old('is_active', true) : true)>

                            <label class="form-check-label fw-bold"
                                   for="new_bonus_active">
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
                                   @checked($oldFormContext === 'new_bonus' && old('stop_after_match'))>

                            <label class="form-check-label fw-bold"
                                   for="new_bonus_stop">
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
                            @php
                                $newBonusQuestionIds = collect(
                                    $oldFormContext === 'new_bonus'
                                        ? old('questions', [])
                                        : []
                                )->map(fn ($id) => (int) $id);
                            @endphp

                            <div class="row g-2">
                                @foreach($preseasonTemplates->sortBy('position') as $template)
                                    <div class="col-md-6 col-xl-4">
                                        <label class="border rounded-3 p-2 w-100 bg-white d-flex gap-2 align-items-start">
                                            <input type="checkbox"
                                                   name="questions[]"
                                                   value="{{ $template->id }}"
                                                   class="form-check-input mt-1"
                                                   @checked($newBonusQuestionIds->contains($template->id))>

                                            <span>
                                                <span class="fw-bold d-block">
                                                    {{ $template->position }}. {{ $template->label }}
                                                </span>

                                                <span class="text-muted small">
                                                    {{ $template->profile->name ?? 'Aucun barème' }}

                                                    @if($template->correctionGroups->isNotEmpty())
                                                        ·
                                                        @foreach($template->correctionGroups as $group)
                                                            <a href="#global-correction-group-{{ $group->id }}"
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
                        <button class="btn btn-warning rounded-pill fw-bold px-4"
                                @disabled($preseasonTemplates->isEmpty())>
                            Ajouter le bonus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Barèmes techniques
                    </h3>

                    <p class="text-muted mb-0">
                        Les questions utilisent ces barèmes. Cette partie est volontairement placée en bas pour laisser la configuration fonctionnelle au premier plan.
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill fw-bold"
                            data-bs-toggle="collapse"
                            data-bs-target="#globalPreseasonScoringProfiles"
                            aria-expanded="false"
                            aria-controls="globalPreseasonScoringProfiles">
                        Afficher les barèmes ({{ $profiles->count() }})
                    </button>

                    <a href="{{ route('admin.settings.scoring-profiles.create', ['return_to' => 'preseason', 'category' => 'preseason']) }}"
                       class="btn btn-warning rounded-pill fw-bold px-4">
                        + Créer un barème
                    </a>
                </div>
            </div>

            <div class="collapse mt-4"
                 id="globalPreseasonScoringProfiles">
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

<div class="modal fade"
     id="deleteConfirmModal"
     tabindex="-1"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    Confirmer la suppression
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fermer">
                </button>
            </div>

            <div class="modal-body">
                Supprimer définitivement :
                <strong id="deleteConfirmLabel"></strong>
                ?
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill"
                        data-bs-dismiss="modal">
                    Annuler
                </button>

                <button type="button"
                        class="btn btn-danger rounded-pill fw-bold"
                        id="deleteConfirmButton">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .preseason-questions-table {
        min-width: 1280px;
        font-size: 0.82rem;
    }

    .preseason-questions-table th {
        white-space: nowrap;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #6c757d;
    }

    .preseason-questions-table td {
        vertical-align: top;
    }

    .preseason-questions-table .form-control,
    .preseason-questions-table .form-select,
    .preseason-questions-table .input-group-text {
        font-size: 0.78rem;
        padding-top: 0.22rem;
        padding-bottom: 0.22rem;
    }

    .preseason-questions-table .question-column {
        min-width: 330px;
        width: 330px;
    }

    .preseason-questions-table .question-label-input {
        min-width: 310px;
    }

    .preseason-questions-table .auto-column {
        min-width: 240px;
        width: 240px;
    }

    .preseason-questions-table .profile-column {
        min-width: 220px;
        width: 220px;
    }

    .preseason-questions-table .auto-journee-select {
        width: 82px;
        margin: 0 auto;
    }

    .preseason-questions-table .auto-position-select,
    .preseason-questions-table .position-input {
        width: 72px;
        margin: 0 auto;
    }

    .preseason-questions-table .compact-help {
        margin-top: 0.2rem;
        font-size: 0.68rem;
        line-height: 1.15;
    }

    .drag-handle {
        cursor: grab;
    }

    .delete-icon-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.9rem;
        height: 1.9rem;
        padding: 0;
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1;
    }

    .correction-group-target {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .preseason-questions-table select:disabled {
        color: var(--bs-secondary-color);
        background-color: var(--bs-secondary-bg);
    }
</style>
@endpush

@push('scripts')
<script>
    let pendingDeleteFormId = null;

    function submitDeleteForm(formId, label) {
        const form = document.getElementById(formId);

        if (!form) {
            return;
        }

        pendingDeleteFormId = formId;

        const labelElement = document.getElementById(
            'deleteConfirmLabel'
        );

        if (labelElement) {
            labelElement.textContent = label || '';
        }

        const modalElement = document.getElementById(
            'deleteConfirmModal'
        );

        if (window.bootstrap && modalElement) {
            window.bootstrap.Modal
                .getOrCreateInstance(modalElement)
                .show();

            return;
        }

        if (confirm('Supprimer définitivement : ' + (label || 'cet élément') + ' ?')) {
            form.submit();
        }
    }

    function toggleBonusQuestions(id) {
        togglePanel(
            'bonus_questions_' + id,
            'bonus_questions_icon_' + id
        );
    }

    function toggleCorrectionGroupQuestions(id) {
        togglePanel(
            'correction_group_questions_' + id,
            'correction_group_questions_icon_' + id
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
            icon.textContent = panel.classList.contains('d-none')
                ? '+'
                : '−';
        }
    }

    function refreshPositions(
        container,
        itemSelector,
        positionSelector
    ) {
        container
            .querySelectorAll(itemSelector)
            .forEach(function (item, index) {
                const positionInput = item.querySelector(
                    positionSelector
                );

                if (positionInput) {
                    positionInput.value = (index + 1) * 10;
                }
            });
    }

    function persistOrder(
        container,
        itemSelector,
        endpoint,
        payloadKey
    ) {
        const ids = Array.from(
            container.querySelectorAll(itemSelector)
        ).map(function (item) {
            return item.dataset.id;
        });

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
            body: JSON.stringify({
                [payloadKey]: ids,
            }),
        }).catch(function (error) {
            console.error('Impossible d’enregistrer le nouvel ordre.', error);
        });
    }

    function setupReorderableList(
        containerSelector,
        itemSelector,
        positionSelector,
        endpoint,
        payloadKey
    ) {
        const container = document.querySelector(containerSelector);

        if (!container) {
            return;
        }

        let draggedItem = null;
        let dragHandleIsPressed = false;

        container.addEventListener('pointerdown', function (event) {
            dragHandleIsPressed = Boolean(
                event.target.closest('.drag-handle')
            );
        });

        document.addEventListener('pointerup', function () {
            dragHandleIsPressed = false;
        });

        container.addEventListener('dragstart', function (event) {
            const item = event.target.closest(itemSelector);

            if (!item || !dragHandleIsPressed) {
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
            dragHandleIsPressed = false;

            refreshPositions(
                container,
                itemSelector,
                positionSelector
            );

            persistOrder(
                container,
                itemSelector,
                endpoint,
                payloadKey
            );
        });

        container.addEventListener('dragover', function (event) {
            event.preventDefault();

            const target = event.target.closest(itemSelector);

            if (!draggedItem || !target || draggedItem === target) {
                return;
            }

            const rectangle = target.getBoundingClientRect();
            const next = (
                event.clientY - rectangle.top
            ) / rectangle.height > 0.5;

            container.insertBefore(
                draggedItem,
                next ? target.nextSibling : target
            );
        });

        refreshPositions(
            container,
            itemSelector,
            positionSelector
        );
    }

    function highlightCorrectionGroupTarget(targetId) {
        const target = document.getElementById(targetId);

        if (!target) {
            return;
        }

        target.classList.add('border-primary', 'shadow');

        window.setTimeout(function () {
            target.classList.remove('border-primary', 'shadow');
        }, 1800);
    }

    function setupCorrectionGroupAnchorLinks() {
        document
            .querySelectorAll('.correction-group-anchor-link')
            .forEach(function (link) {
                link.addEventListener('click', function () {
                    const href = link.getAttribute('href');

                    if (!href || !href.startsWith('#')) {
                        return;
                    }

                    const targetId = href.substring(1);

                    window.setTimeout(function () {
                        highlightCorrectionGroupTarget(targetId);
                    }, 150);
                });
            });

        if (
            window.location.hash
            && window.location.hash.startsWith('#global-correction-group-')
        ) {
            window.setTimeout(function () {
                highlightCorrectionGroupTarget(
                    window.location.hash.substring(1)
                );
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
                behavior: 'smooth',
            });
        });

        window.addEventListener('scroll', refreshButtonVisibility, {
            passive: true,
        });

        refreshButtonVisibility();
    }

    function optionCompatibleWithAnswerType(option, answerType) {
        if (!option.value) {
            return true;
        }

        const compatibleTypes = (
            option.dataset.compatibleAnswerTypes || ''
        )
            .split(',')
            .filter(Boolean);

        return compatibleTypes.includes(answerType);
    }

    function selectedRuleOption(ruleSelect) {
        return ruleSelect.options[ruleSelect.selectedIndex] || null;
    }

    function refreshAutoResultFields(
        typeSelect,
        ruleSelect,
        journeeSelect,
        positionSelect,
        helpElement
    ) {
        if (
            !typeSelect
            || !ruleSelect
            || !journeeSelect
            || !positionSelect
        ) {
            return;
        }

        const answerType = typeSelect.value;

        Array.from(ruleSelect.options).forEach(function (option) {
            option.hidden = !optionCompatibleWithAnswerType(
                option,
                answerType
            );
        });

        const currentOption = selectedRuleOption(ruleSelect);

        if (
            currentOption
            && currentOption.value
            && !optionCompatibleWithAnswerType(
                currentOption,
                answerType
            )
        ) {
            ruleSelect.value = '';
        }

        const option = selectedRuleOption(ruleSelect);
        const hasRule = Boolean(ruleSelect.value);

        const requiresJournee = option
            && option.dataset.requiresJournee === '1';

        const requiresPosition = option
            && option.dataset.requiresPosition === '1';

        journeeSelect.disabled = !hasRule || !requiresJournee;
        positionSelect.disabled = !hasRule || !requiresPosition;

        if (!requiresJournee) {
            journeeSelect.value = '';
        }

        if (!requiresPosition) {
            positionSelect.value = '';
        }

        if (helpElement) {
            if (!hasRule) {
                helpElement.textContent = 'Aucun calcul auto';
            } else if (requiresPosition) {
                helpElement.textContent = 'J + position';
            } else if (requiresJournee) {
                helpElement.textContent = 'J obligatoire';
            } else {
                helpElement.textContent = 'Selon match';
            }
        }
    }

    function setupAutoResultFields() {
        document
            .querySelectorAll('.js-question-answer-type')
            .forEach(function (typeSelect) {
                const questionId = typeSelect.dataset.questionId;

                const ruleSelect = document.querySelector(
                    '.js-auto-result-rule[data-question-id="'
                        + questionId
                        + '"]'
                );

                const journeeSelect = document.querySelector(
                    '.js-auto-result-journee[data-question-id="'
                        + questionId
                        + '"]'
                );

                const positionSelect = document.querySelector(
                    '.js-auto-result-position[data-question-id="'
                        + questionId
                        + '"]'
                );

                const helpElement = document.querySelector(
                    '.js-auto-result-help[data-question-id="'
                        + questionId
                        + '"]'
                );

                function refresh() {
                    refreshAutoResultFields(
                        typeSelect,
                        ruleSelect,
                        journeeSelect,
                        positionSelect,
                        helpElement
                    );
                }

                typeSelect.addEventListener('change', refresh);
                ruleSelect?.addEventListener('change', refresh);

                refresh();
            });

        const newTypeSelect = document.querySelector(
            '.js-new-question-answer-type'
        );

        const newRuleSelect = document.querySelector(
            '.js-new-auto-result-rule'
        );

        const newJourneeSelect = document.querySelector(
            '.js-new-auto-result-journee'
        );

        const newPositionSelect = document.querySelector(
            '.js-new-auto-result-position'
        );

        function refreshNewQuestionAutoFields() {
            refreshAutoResultFields(
                newTypeSelect,
                newRuleSelect,
                newJourneeSelect,
                newPositionSelect,
                null
            );
        }

        newTypeSelect?.addEventListener(
            'change',
            refreshNewQuestionAutoFields
        );

        newRuleSelect?.addEventListener(
            'change',
            refreshNewQuestionAutoFields
        );

        refreshNewQuestionAutoFields();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const deleteButton = document.getElementById(
            'deleteConfirmButton'
        );

        deleteButton?.addEventListener('click', function () {
            if (!pendingDeleteFormId) {
                return;
            }

            const form = document.getElementById(
                pendingDeleteFormId
            );

            form?.submit();
        });

        setupReorderableList(
            '#preseasonTemplatesList',
            'tr',
            '.template-position-input',
            @json(route('admin.settings.preseason-templates.reorder')),
            'templates'
        );

        setupReorderableList(
            '#preseasonCorrectionGroupsList',
            '.list-group-item',
            '.correction-group-position-input',
            @json(route('admin.settings.preseason-correction-groups.reorder')),
            'correction_groups'
        );

        setupReorderableList(
            '#preseasonBonusRulesList',
            '.list-group-item',
            '.bonus-position-input',
            @json(route('admin.settings.preseason-bonus-rules.reorder')),
            'bonus_rules'
        );

        const questionsForm = document.getElementById(
            'preseasonTemplatesForm'
        );

        questionsForm?.addEventListener('submit', function () {
            const container = document.querySelector(
                '#preseasonTemplatesList'
            );

            if (container) {
                refreshPositions(
                    container,
                    'tr',
                    '.template-position-input'
                );
            }
        });

        setupCorrectionGroupAnchorLinks();
        setupBackToTopButton();
        setupAutoResultFields();
    });
</script>
@endpush
