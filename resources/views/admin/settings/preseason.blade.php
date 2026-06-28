@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.settings.index'),
    'label' => 'Retour administration',
])

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Paramètres avant-saison
        </h2>

        <p class="text-muted mb-0">
            Gère les barèmes, les questions et les bonus utilisés pour les pronostics avant-saison.
        </p>
    </div>
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

<div class="rugby-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1">
                Barèmes avant-saison
            </h3>

            <p class="text-muted mb-0">
                Barèmes disponibles pour les questions avant-saison.
            </p>
        </div>

        <a href="{{ route('admin.settings.scoring-profiles.create', [
                'category' => 'preseason',
                'return_to' => 'preseason',
            ]) }}"
           class="btn btn-sm btn-warning rounded-pill fw-bold px-3">
            + Créer un barème
        </a>
    </div>

    @if($profiles->isEmpty())
        <div class="alert alert-info mb-0">
            Aucun barème avant-saison n’est encore configuré.
        </div>
    @else
        <div class="row g-3">
            @foreach($profiles as $profile)
                <div class="col-md-6">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="fw-bold">
                                    {{ $profile->name }}
                                </div>

                                <div class="small text-muted">
                                    {{ $profile->code }}
                                </div>
                            </div>

                            <a href="{{ route('admin.settings.scoring-profiles.edit', [
                                    'profile' => $profile,
                                    'return_to' => 'preseason',
                                ]) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                Modifier
                            </a>
                        </div>

                        @if($profile->description)
                            <div class="small text-muted mb-2">
                                {{ $profile->description }}
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            @foreach($profile->rules as $rule)
                                <span class="badge text-bg-light border text-dark rounded-pill">
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

<div class="rugby-card p-4 mb-4">
    <h3 class="h5 fw-bold mb-1">
        Questions avant-saison
    </h3>

    <p class="text-muted mb-4">
        Les questions sont affichées dans cet ordre aux joueurs. Les groupes de correction permettent de corriger plusieurs questions ensemble, par exemple les demi-finalistes sans tenir compte de l’ordre.
    </p>

    <datalist id="correction-group-suggestions">
        <option value="top14_semifinalists">
        <option value="prod2_semifinalists">
    </datalist>

    <div class="border rounded-4 p-3 mb-4">
        <h4 class="h6 fw-bold mb-3">
            Ajouter une question
        </h4>

        <form method="POST" action="{{ route('admin.settings.preseason-templates.store') }}">
            @csrf

            <div class="row g-3 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label fw-bold">
                        Libellé
                    </label>

                    <input name="label"
                           value="{{ old('label') }}"
                           class="form-control"
                           required>
                </div>

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Type de réponse
                    </label>

                    <select name="answer_type" class="form-select" required>
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

                <div class="col-lg-2">
                    <label class="form-label fw-bold">
                        Groupe de correction
                    </label>

                    <input name="correction_group"
                           value="{{ old('correction_group') }}"
                           list="correction-group-suggestions"
                           class="form-control"
                           placeholder="ex: top14_semifinalists">

                    <div class="form-text">
                        Vide = correction question par question.
                    </div>
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

                    <select name="scoring_profile_id" class="form-select" required>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}"
                                    @selected((string) old('scoring_profile_id') === (string) $profile->id)>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-1">
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="form-check-input"
                               id="new-template-active"
                               @checked(old('is_active', true))>

                        <label for="new-template-active" class="form-check-label">
                            Actif
                        </label>
                    </div>

                    <button class="btn btn-warning rounded-pill fw-bold w-100">
                        +
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($preseasonTemplates->isEmpty())
        <div class="alert alert-info mb-0">
            Aucune question avant-saison n’est encore configurée.
        </div>
    @else
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 42px;"></th>
                            <th>Question</th>
                            <th style="width: 170px;">Type</th>
                            <th style="width: 220px;">Groupe</th>
                            <th style="width: 150px;">Mode</th>
                            <th style="width: 220px;">Barème</th>
                            <th class="text-center" style="width: 90px;">Position</th>
                            <th class="text-center" style="width: 80px;">Actif</th>
                            <th class="text-end" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="preseason-templates-list">
                        @foreach($preseasonTemplates as $template)
                            <tr class="preseason-template-item"
                                data-id="{{ $template->id }}">
                                <td class="text-muted">
                                    <span class="drag-handle" role="button">
                                        ↕
                                    </span>
                                </td>

                                <td>
                                    <input name="preseason[{{ $template->id }}][label]"
                                           value="{{ old("preseason.{$template->id}.label", $template->label) }}"
                                           class="form-control"
                                           required>
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
                                    <input name="preseason[{{ $template->id }}][correction_group]"
                                           value="{{ old("preseason.{$template->id}.correction_group", $template->correction_group) }}"
                                           list="correction-group-suggestions"
                                           class="form-control"
                                           placeholder="ex: top14_semifinalists">
                                </td>

                                <td>
                                    <select name="preseason[{{ $template->id }}][correction_mode]"
                                            class="form-select">
                                        <option value="" @selected(old("preseason.{$template->id}.correction_mode", $template->correction_mode) === null || old("preseason.{$template->id}.correction_mode", $template->correction_mode) === '')>
                                            Normal
                                        </option>

                                        <option value="unordered" @selected(old("preseason.{$template->id}.correction_mode", $template->correction_mode) === 'unordered')>
                                            Sans ordre
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <select name="preseason[{{ $template->id }}][scoring_profile_id]"
                                            class="form-select"
                                            required>
                                        @foreach($profiles as $profile)
                                            <option value="{{ $profile->id }}"
                                                    @selected((string) old("preseason.{$template->id}.scoring_profile_id", $template->scoring_profile_id) === (string) $profile->id)>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input name="preseason[{{ $template->id }}][position]"
                                           value="{{ old("preseason.{$template->id}.position", $template->position) }}"
                                           type="number"
                                           class="form-control text-center template-position-input">
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
                                            class="btn btn-sm btn-outline-danger rounded-pill js-delete-item"
                                            data-delete-url="{{ route('admin.settings.preseason-templates.destroy', $template) }}"
                                            data-delete-label="{{ $template->label }}">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer les questions
            </button>
        </form>
    @endif
</div>

<div class="rugby-card p-4">
    <h3 class="h5 fw-bold mb-1">
        Bonus avant-saison
    </h3>

    <p class="text-muted mb-4">
        Bonus accordés quand plusieurs pronostics avant-saison sont corrects. L’ordre est important : les bonus sont évalués de haut en bas.
    </p>

    <div class="border rounded-4 p-3 mb-4">
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
                           class="form-control"
                           required>
                </div>

                <div class="col-md-2 d-flex align-items-end">
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
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox"
                               name="stop_after_match"
                               value="1"
                               id="new-bonus-stop"
                               class="form-check-input"
                               @checked(old('stop_after_match'))>

                        <label for="new-bonus-stop" class="form-check-label">
                            Stop après obtention
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-bold">
                    Questions nécessaires
                </label>

                <div class="row g-2">
                    @foreach($preseasonTemplates as $template)
                        <div class="col-md-6">
                            <label class="border rounded-3 p-2 d-flex gap-2 align-items-start h-100">
                                <input type="checkbox"
                                       name="questions[]"
                                       value="{{ $template->id }}"
                                       class="form-check-input mt-1">

                                <span>
                                    <span class="fw-bold">
                                        {{ $template->label }}
                                    </span>

                                    <span class="d-block small text-muted">
                                        {{ $template->profile->name ?? 'Aucun barème' }}

                                        @if($template->correction_group)
                                            · {{ $template->correction_group }}

                                            @if($template->correction_mode === 'unordered')
                                                · sans ordre
                                            @endif
                                        @endif
                                    </span>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-warning rounded-pill fw-bold px-4 mt-3">
                Enregistrer le bonus
            </button>
        </form>
    </div>

    @if($preseasonBonusRules->isEmpty())
        <div class="alert alert-info mb-0">
            Aucun bonus avant-saison n’est encore configuré.
        </div>
    @else
        <div id="preseason-bonus-rules-list" class="d-flex flex-column gap-3">
            @foreach($preseasonBonusRules as $bonusRule)
                <div class="border rounded-4 p-3 preseason-bonus-rule-item"
                     data-id="{{ $bonusRule->id }}">
                    <form method="POST"
                          action="{{ route('admin.settings.preseason-bonus-rules.update', $bonusRule) }}">
                        @csrf
                        @method('PUT')

                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="drag-handle text-muted" role="button">
                                        ↕
                                    </span>

                                    <h4 class="h6 fw-bold mb-0">
                                        {{ $bonusRule->label }}
                                    </h4>
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge text-bg-warning rounded-pill">
                                        +{{ $bonusRule->points }} pts
                                    </span>

                                    @if($bonusRule->stop_after_match)
                                        <span class="badge text-bg-info rounded-pill">
                                            Stop
                                        </span>
                                    @else
                                        <span class="badge text-bg-light border text-dark rounded-pill">
                                            Cumulable
                                        </span>
                                    @endif

                                    @if($bonusRule->is_active)
                                        <span class="badge text-bg-success rounded-pill">
                                            Actif
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">
                                            Inactif
                                        </span>
                                    @endif

                                    <span class="badge text-bg-light border text-dark rounded-pill">
                                        {{ $bonusRule->questions->count() }} question(s) nécessaire(s)
                                    </span>
                                </div>
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-danger rounded-pill js-delete-item"
                                    data-delete-url="{{ route('admin.settings.preseason-bonus-rules.destroy', $bonusRule) }}"
                                    data-delete-label="{{ $bonusRule->label }}">
                                Supprimer
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">
                                    Libellé
                                </label>

                                <input name="label"
                                       value="{{ old("bonus_rules.{$bonusRule->id}.label", $bonusRule->label) }}"
                                       class="form-control"
                                       required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold">
                                    Points
                                </label>

                                <input name="points"
                                       type="number"
                                       value="{{ old("bonus_rules.{$bonusRule->id}.points", $bonusRule->points) }}"
                                       class="form-control"
                                       required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold">
                                    Position
                                </label>

                                <input name="position"
                                       type="number"
                                       value="{{ old("bonus_rules.{$bonusRule->id}.position", $bonusRule->position) }}"
                                       class="form-control bonus-position-input">
                            </div>

                            <div class="col-md-3 d-flex align-items-end gap-4">
                                <div class="form-check mb-2">
                                    <input type="checkbox"
                                           name="is_active"
                                           value="1"
                                           id="bonus-active-{{ $bonusRule->id }}"
                                           class="form-check-input"
                                           @checked(old("bonus_rules.{$bonusRule->id}.is_active", $bonusRule->is_active))>

                                    <label for="bonus-active-{{ $bonusRule->id }}" class="form-check-label">
                                        Actif
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input type="checkbox"
                                           name="stop_after_match"
                                           value="1"
                                           id="bonus-stop-{{ $bonusRule->id }}"
                                           class="form-check-input"
                                           @checked(old("bonus_rules.{$bonusRule->id}.stop_after_match", $bonusRule->stop_after_match))>

                                    <label for="bonus-stop-{{ $bonusRule->id }}" class="form-check-label">
                                        Stop
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="fw-bold mb-2">
                                Questions liées
                            </div>

                            <div class="row g-2">
                                @foreach($preseasonTemplates as $template)
                                    <div class="col-md-6">
                                        <label class="border rounded-3 p-2 d-flex gap-2 align-items-start h-100">
                                            <input type="checkbox"
                                                   name="questions[]"
                                                   value="{{ $template->id }}"
                                                   class="form-check-input mt-1"
                                                   @checked($bonusRule->questions->contains($template->id))>

                                            <span>
                                                <span class="fw-bold">
                                                    {{ $template->label }}
                                                </span>

                                                <span class="d-block small text-muted">
                                                    {{ $template->profile->name ?? 'Aucun barème' }}

                                                    @if($template->correction_group)
                                                        · {{ $template->correction_group }}

                                                        @if($template->correction_mode === 'unordered')
                                                            · sans ordre
                                                        @endif
                                                    @endif
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <button class="btn btn-outline-primary rounded-pill fw-bold px-4 mt-3">
                            Enregistrer ce bonus
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="deleteItemModalLabel">
                    Confirmer la suppression
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fermer"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Supprimer définitivement :
                    <span id="deleteItemLabel" class="fw-bold"></span> ?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill"
                        data-bs-dismiss="modal">
                    Annuler
                </button>

                <button type="button"
                        class="btn btn-danger rounded-pill fw-bold"
                        id="confirmDeleteItemButton">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = @json(csrf_token());

        const templateList = document.getElementById('preseason-templates-list');

        if (templateList && window.Sortable) {
            new Sortable(templateList, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () {
                    const templateIds = Array.from(templateList.querySelectorAll('.preseason-template-item'))
                        .map(function (row, index) {
                            const positionInput = row.querySelector('.template-position-input');

                            if (positionInput) {
                                positionInput.value = (index + 1) * 10;
                            }

                            return row.dataset.id;
                        });

                    fetch(@json(route('admin.settings.preseason-templates.reorder')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            templates: templateIds,
                        }),
                    });
                },
            });
        }

        const bonusList = document.getElementById('preseason-bonus-rules-list');

        if (bonusList && window.Sortable) {
            new Sortable(bonusList, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () {
                    const bonusRuleIds = Array.from(bonusList.querySelectorAll('.preseason-bonus-rule-item'))
                        .map(function (card, index) {
                            const positionInput = card.querySelector('.bonus-position-input');

                            if (positionInput) {
                                positionInput.value = (index + 1) * 10;
                            }

                            return card.dataset.id;
                        });

                    fetch(@json(route('admin.settings.preseason-bonus-rules.reorder')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            bonus_rules: bonusRuleIds,
                        }),
                    });
                },
            });
        }

        const deleteModalElement = document.getElementById('deleteItemModal');
        const deleteLabelElement = document.getElementById('deleteItemLabel');
        const confirmDeleteButton = document.getElementById('confirmDeleteItemButton');

        let deleteUrl = null;
        let deleteTrigger = null;

        document.querySelectorAll('.js-delete-item').forEach(function (button) {
            button.addEventListener('click', function () {
                deleteUrl = button.dataset.deleteUrl;
                deleteTrigger = button;

                if (deleteLabelElement) {
                    deleteLabelElement.textContent = button.dataset.deleteLabel || '';
                }

                if (deleteModalElement && window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(deleteModalElement).show();
                }
            });
        });

        if (confirmDeleteButton) {
            confirmDeleteButton.addEventListener('click', function () {
                if (! deleteUrl) {
                    return;
                }

                fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (! response.ok) {
                            throw new Error('Delete failed');
                        }

                        const item = deleteTrigger?.closest('.preseason-template-item, .preseason-bonus-rule-item');

                        if (item) {
                            item.remove();
                        } else {
                            window.location.reload();
                        }

                        if (deleteModalElement && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(deleteModalElement).hide();
                        }
                    })
                    .catch(function () {
                        window.location.reload();
                    });
            });
        }
    });
</script>
@endpush
