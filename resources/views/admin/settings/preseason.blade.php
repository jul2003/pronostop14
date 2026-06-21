@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour administration
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Paramètres avant-saison
    </h2>

    <p class="text-muted mb-0">
        Gère les barèmes, les questions et les bonus utilisés pour les pronostics avant-saison.
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
                        Les questions sont affichées dans cet ordre aux joueurs. Déplace-les avec la poignée.
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
                                   required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Type de réponse
                            </label>

                            <select name="answer_type"
                                    class="form-select"
                                    required>
                                <option value="top14_club">
                                    Club TOP 14
                                </option>

                                <option value="prod2_club">
                                    Club PRO D2
                                </option>

                                <option value="season_club">
                                    Club de la saison
                                </option>

                                <option value="free_text">
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
                                    <option value="{{ $profile->id }}">
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
                                       checked>

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
                <form method="POST" action="{{ route('admin.settings.update') }}">
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
                                    <tr data-id="{{ $template->id }}">
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-light border drag-handle rounded-pill"
                                                    title="Déplacer">
                                                ↕
                                            </button>

                                            <input type="hidden"
                                                   name="preseason[{{ $template->id }}][position]"
                                                   value="{{ $template->position }}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="preseason[{{ $template->id }}][label]"
                                                   value="{{ $template->label }}"
                                                   class="form-control form-control-sm"
                                                   required>
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][answer_type]"
                                                    class="form-select form-select-sm"
                                                    required>
                                                <option value="top14_club" @selected($template->answer_type === 'top14_club')>
                                                    Club TOP 14
                                                </option>

                                                <option value="prod2_club" @selected($template->answer_type === 'prod2_club')>
                                                    Club PRO D2
                                                </option>

                                                <option value="season_club" @selected($template->answer_type === 'season_club')>
                                                    Club saison
                                                </option>

                                                <option value="free_text" @selected($template->answer_type === 'free_text')>
                                                    Champ libre
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][scoring_profile_id]"
                                                    class="form-select form-select-sm"
                                                    required>
                                                @foreach($profiles as $profile)
                                                    <option value="{{ $profile->id }}"
                                                        @selected($template->scoring_profile_id === $profile->id)>
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
                                                   @checked($template->is_active)>
                                        </td>

                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="deletePreseasonTemplate({{ $template->id }}, this)">
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer les questions
                        </button>
                    </div>
                </form>
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
                        Bonus accordés quand plusieurs pronostics avant-saison sont corrects.
                        L’ordre est important : les bonus sont évalués de haut en bas.
                    </p>
                </div>
            </div>

            <div class="border rounded-4 bg-light overflow-hidden mb-4">
                <button type="button"
                        class="btn w-100 text-start p-3 d-flex justify-content-between align-items-center fw-bold"
                        onclick="togglePanel('new_bonus_panel', 'new_bonus_icon')">
                    <span>
                        + Ajouter un bonus
                    </span>

                    <span id="new_bonus_icon">
                        +
                    </span>
                </button>

                <div id="new_bonus_panel"
                     class="p-3 p-md-4 border-top d-none">
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
                                       placeholder="Perfect avant-saison"
                                       required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold">
                                    Points
                                </label>

                                <input type="number"
                                       name="points"
                                       class="form-control text-center"
                                       value="0"
                                       required>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="is_active"
                                           value="1"
                                           class="form-check-input"
                                           id="new_bonus_active"
                                           checked>

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
                                           id="new_bonus_stop">

                                    <label class="form-check-label fw-bold" for="new_bonus_stop">
                                        Stop après obtention
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Questions nécessaires
                                </label>

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
                            </div>

                            <div class="col-12">
                                <button class="btn btn-warning rounded-pill fw-bold px-4">
                                    Enregistrer le bonus
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($preseasonBonusRules->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun bonus avant-saison n’est encore configuré.
                </div>
            @else
                <div id="preseasonBonusRulesList" class="d-grid gap-3">
                    @foreach($preseasonBonusRules as $bonusRule)
                        <div class="border rounded-4 p-3 p-md-4 bg-white list-group-item"
                             data-id="{{ $bonusRule->id }}">

                            <form method="POST"
                                  action="{{ route('admin.settings.preseason-bonus-rules.update', $bonusRule) }}">
                                @csrf
                                @method('PUT')

                                <input type="hidden"
                                       name="position"
                                       value="{{ $bonusRule->position }}">

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
                                            onclick="deletePreseasonBonusRule({{ $bonusRule->id }}, this)">
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
                                               value="{{ $bonusRule->label }}"
                                               class="form-control"
                                               required>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">
                                            Points
                                        </label>

                                        <input type="number"
                                               name="points"
                                               value="{{ $bonusRule->points }}"
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
                                                   @checked($bonusRule->is_active)>

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
                                                   @checked($bonusRule->stop_after_match)>

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
                                                                       @checked($bonusRule->questions->contains($template->id))>

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
                                        <button class="btn btn-warning rounded-pill fw-bold px-4">
                                            Enregistrer ce bonus
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    function refreshQuestionPositions() {
        const rows = document.querySelectorAll('#preseasonTemplatesList tr');

        rows.forEach((row, index) => {
            const positionInput = row.querySelector('input[name$="[position]"]');

            if (positionInput) {
                positionInput.value = (index + 1) * 10;
            }
        });
    }

    function refreshBonusRulePositions() {
        const items = document.querySelectorAll('#preseasonBonusRulesList .list-group-item');

        items.forEach((item, index) => {
            const positionInput = item.querySelector('input[name="position"]');

            if (positionInput) {
                positionInput.value = (index + 1) * 10;
            }
        });
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

    function toggleBonusQuestions(bonusRuleId) {
        togglePanel(`bonus_questions_${bonusRuleId}`, `bonus_questions_icon_${bonusRuleId}`);
    }

    function deletePreseasonTemplate(templateId, button) {
        if (!confirm('Supprimer cette question avant-saison ?')) {
            return;
        }

        fetch(`/admin/parametres/avant-saison/${templateId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Suppression impossible.');
                }

                const row = button.closest('tr');

                if (row) {
                    row.remove();
                    refreshQuestionPositions();
                }
            })
            .catch(() => {
                alert('La suppression a échoué.');
            });
    }

    function deletePreseasonBonusRule(bonusRuleId, button) {
        if (!confirm('Supprimer ce bonus avant-saison ?')) {
            return;
        }

        fetch(`/admin/parametres/bonus-avant-saison/${bonusRuleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Suppression impossible.');
                }

                const item = button.closest('.list-group-item');

                if (item) {
                    item.remove();
                    refreshBonusRulePositions();
                }
            })
            .catch(() => {
                alert('La suppression a échoué.');
            });
    }

    window.addEventListener('load', function () {
        const templatesList = document.getElementById('preseasonTemplatesList');

        if (templatesList && window.Sortable) {
            new window.Sortable(templatesList, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',

                onEnd: function () {
                    refreshQuestionPositions();

                    const templates = [...templatesList.querySelectorAll('tr')]
                        .map(row => row.dataset.id);

                    fetch("{{ route('admin.settings.preseason-templates.reorder') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ templates }),
                    });
                },
            });
        }

        const bonusRulesList = document.getElementById('preseasonBonusRulesList');

        if (bonusRulesList && window.Sortable) {
            new window.Sortable(bonusRulesList, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',

                onEnd: function () {
                    refreshBonusRulePositions();

                    const bonusRules = [...bonusRulesList.querySelectorAll('.list-group-item')]
                        .map(item => item.dataset.id);

                    fetch("{{ route('admin.settings.preseason-bonus-rules.reorder') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ bonus_rules: bonusRules }),
                    });
                },
            });
        }

        refreshQuestionPositions();
        refreshBonusRulePositions();
    });
</script>
@endpush
