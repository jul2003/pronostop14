@extends('layouts.pronos')

@section('content')

@php
    $isEdit = $profile !== null;

    $currentCategory = old(
        'category',
        $defaultCategory ?? $profile?->category ?? 'journee'
    );

    $backRoute = $returnTo === 'preseason'
        ? route('admin.settings.preseason')
        : route('admin.settings.index');

    $backLabel = $returnTo === 'preseason'
        ? 'Retour aux paramètres avant-saison'
        : 'Retour aux paramètres des barèmes';

    $submittedRules = old('rules');

    if ($submittedRules !== null) {
        $ruleRows = collect($submittedRules)->values();
    } elseif ($profile) {
        $ruleRows = $profile->rules
            ->sortBy('position')
            ->values()
            ->map(function ($rule) {
                return [
                    'code' => $rule->code,
                    'label' => $rule->label,
                    'points' => $rule->points,
                    'position' => $rule->position,
                ];
            });
    } else {
        $ruleRows = collect();
    }
@endphp

@include('admin.partials.back-link', [
    'href' => $backRoute,
    'label' => $backLabel,
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        {{ $isEdit ? 'Modifier le barème' : 'Créer un barème' }}
    </h2>

    <p class="text-muted mb-0">
        Un barème peut contenir uniquement les règles connues par l’application.
        Une règle déjà utilisée dans ce barème n’est plus proposée dans les autres lignes.
    </p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-2">
            Le formulaire contient des erreurs.
        </div>

        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="rugby-card p-4">
    <form method="POST"
          action="{{ $isEdit
              ? route('admin.settings.scoring-profiles.update', $profile)
              : route('admin.settings.scoring-profiles.store') }}">
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        @if($returnTo)
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @endif

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <label class="form-label fw-bold">
                    Code du barème
                </label>

                @if($isEdit)
                    <input type="text"
                           value="{{ $profile->code }}"
                           class="form-control"
                           disabled>

                    <div class="form-text">
                        Le code du barème n’est pas modifiable après création.
                    </div>
                @else
                    <input type="text"
                           name="code"
                           value="{{ old('code') }}"
                           class="form-control"
                           required>
                @endif
            </div>

            <div class="col-lg-4">
                <label class="form-label fw-bold">
                    Catégorie
                </label>

                @if($isEdit)
                    <input type="text"
                           value="{{ $profile->category === 'preseason' ? 'Avant-saison' : 'Journée' }}"
                           class="form-control"
                           disabled
                           data-category-value="{{ $profile->category }}">
                @else
                    <select name="category"
                            class="form-select"
                            data-category-select
                            required>
                        <option value="journee"
                                @selected($currentCategory === 'journee')>
                            Journée
                        </option>

                        <option value="preseason"
                                @selected($currentCategory === 'preseason')>
                            Avant-saison
                        </option>
                    </select>
                @endif
            </div>

            <div class="col-lg-4">
                <label class="form-label fw-bold">
                    Position
                </label>

                <input type="number"
                       name="position"
                       value="{{ old('position', $profile?->position ?? 0) }}"
                       class="form-control">
            </div>

            <div class="col-lg-6">
                <label class="form-label fw-bold">
                    Nom
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $profile?->name) }}"
                       class="form-control"
                       required>
            </div>

            <div class="col-lg-6">
                <label class="form-label fw-bold">
                    Arrêter le calcul si le résultat est faux
                </label>

                <input type="hidden" name="stop_on_wrong_result" value="0">

                <div class="form-check mt-2">
                    <input type="checkbox"
                           name="stop_on_wrong_result"
                           value="1"
                           id="stop_on_wrong_result"
                           class="form-check-input"
                           @checked(old('stop_on_wrong_result', $profile?->stop_on_wrong_result ?? true))>

                    <label for="stop_on_wrong_result"
                           class="form-check-label">
                        Oui
                    </label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">
                    Description
                </label>

                <textarea name="description"
                          class="form-control"
                          rows="3">{{ old('description', $profile?->description) }}</textarea>
            </div>
        </div>

        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">
                    Règles du barème
                </h4>

                <p class="text-muted mb-0">
                    Le code est choisi parmi les règles encore disponibles pour ce barème.
                    Les positions sont recalculées automatiquement après suppression.
                </p>
            </div>

            <button type="button"
                    class="btn btn-outline-primary rounded-pill fw-bold"
                    id="add-rule-button">
                + Ajouter une règle
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 32%;">
                            Code
                        </th>
                        <th>
                            Libellé
                        </th>
                        <th class="text-center" style="width: 140px;">
                            Points
                        </th>
                        <th class="text-center" style="width: 140px;">
                            Position
                        </th>
                        <th class="text-end" style="width: 100px;">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody id="rules-table-body">
                    @foreach($ruleRows as $index => $rule)
                        @php
                            $ruleCode = is_array($rule) ? ($rule['code'] ?? '') : ($rule->code ?? '');
                            $ruleLabel = is_array($rule) ? ($rule['label'] ?? '') : ($rule->label ?? '');
                            $rulePoints = is_array($rule) ? ($rule['points'] ?? 0) : ($rule->points ?? 0);
                            $rulePosition = is_array($rule) ? ($rule['position'] ?? (($index + 1) * 10)) : ($rule->position ?? (($index + 1) * 10));
                        @endphp

                        <tr data-rule-row>
                            <td>
                                <select name="rules[{{ $index }}][code]"
                                        class="form-select js-rule-code"
                                        required>
                                    <option value="">
                                        Choisir une règle...
                                    </option>
                                </select>

                                <input type="hidden"
                                       class="js-initial-code"
                                       value="{{ $ruleCode }}">
                            </td>

                            <td>
                                <input type="text"
                                       name="rules[{{ $index }}][label]"
                                       value="{{ $ruleLabel }}"
                                       class="form-control js-rule-label"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="rules[{{ $index }}][points]"
                                       value="{{ $rulePoints }}"
                                       class="form-control text-center"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="rules[{{ $index }}][position]"
                                       value="{{ $rulePosition }}"
                                       class="form-control text-center js-rule-position">
                            </td>

                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger js-remove-rule">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-3" id="rule-limit-message">
            Toutes les règles disponibles pour cette catégorie sont déjà utilisées.
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-warning rounded-pill fw-bold px-4">
                {{ $isEdit ? 'Enregistrer le barème' : 'Créer le barème' }}
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ruleCodeLabelsByCategory = @json($ruleCodeLabelsByCategory);

        const categorySelect = document.querySelector('[data-category-select]');
        const categoryValueInput = document.querySelector('[data-category-value]');
        const tableBody = document.getElementById('rules-table-body');
        const addRuleButton = document.getElementById('add-rule-button');
        const ruleLimitMessage = document.getElementById('rule-limit-message');

        let nextRuleIndex = tableBody.querySelectorAll('[data-rule-row]').length;

        function currentCategory() {
            if (categorySelect) {
                return categorySelect.value;
            }

            if (categoryValueInput) {
                return categoryValueInput.dataset.categoryValue;
            }

            return 'journee';
        }

        function currentRuleLabels() {
            return ruleCodeLabelsByCategory[currentCategory()] || {};
        }

        function ruleRows() {
            return Array.from(tableBody.querySelectorAll('[data-rule-row]'));
        }

        function ruleCodeSelects() {
            return Array.from(tableBody.querySelectorAll('.js-rule-code'));
        }

        function selectedCodes(exceptSelect = null) {
            return ruleCodeSelects()
                .filter(function (select) {
                    return select !== exceptSelect;
                })
                .map(function (select) {
                    return select.value;
                })
                .filter(function (value) {
                    return value !== '';
                });
        }

        function availableCodes(exceptSelect = null) {
            const labels = currentRuleLabels();
            const used = selectedCodes(exceptSelect);

            return Object.keys(labels).filter(function (code) {
                return ! used.includes(code);
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function optionLabel(code, label) {
            return code + ' — ' + label;
        }

        function rebuildSelect(select) {
            const labels = currentRuleLabels();
            const currentValue = select.value || select.closest('[data-rule-row]').querySelector('.js-initial-code')?.value || '';
            const usedCodes = selectedCodes(select);

            let html = '<option value="">Choisir une règle...</option>';

            Object.entries(labels).forEach(function ([code, label]) {
                if (code !== currentValue && usedCodes.includes(code)) {
                    return;
                }

                html += '<option value="' + escapeHtml(code) + '">'
                    + escapeHtml(optionLabel(code, label))
                    + '</option>';
            });

            select.innerHTML = html;

            if (labels[currentValue]) {
                select.value = currentValue;
            } else {
                select.value = '';
            }

            const initialCodeInput = select.closest('[data-rule-row]').querySelector('.js-initial-code');

            if (initialCodeInput) {
                initialCodeInput.value = '';
            }
        }

        function refreshSelects() {
            ruleCodeSelects().forEach(function (select) {
                rebuildSelect(select);
            });

            refreshAddButton();
        }

        function refreshAddButton() {
            const labels = currentRuleLabels();
            const available = availableCodes();
            const hasEmptySelect = ruleCodeSelects().some(function (select) {
                return select.value === '';
            });

            const disabled = Object.keys(labels).length === 0
                || available.length === 0
                || hasEmptySelect;

            addRuleButton.disabled = disabled;
            ruleLimitMessage.classList.toggle('d-none', ! disabled || hasEmptySelect);
        }

        function refreshRulePositions() {
            ruleRows().forEach(function (row, index) {
                const positionInput = row.querySelector('.js-rule-position');

                if (positionInput) {
                    positionInput.value = (index + 1) * 10;
                }
            });
        }

        function fillLabelFromCode(select) {
            const row = select.closest('[data-rule-row]');
            const labelInput = row.querySelector('.js-rule-label');
            const labels = currentRuleLabels();

            if (! labelInput.value && labels[select.value]) {
                labelInput.value = labels[select.value];
            }
        }

        function newRuleRowHtml(index) {
            return `
                <tr data-rule-row>
                    <td>
                        <select name="rules[${index}][code]"
                                class="form-select js-rule-code"
                                required>
                            <option value="">
                                Choisir une règle...
                            </option>
                        </select>

                        <input type="hidden"
                               class="js-initial-code"
                               value="">
                    </td>

                    <td>
                        <input type="text"
                               name="rules[${index}][label]"
                               value=""
                               class="form-control js-rule-label"
                               required>
                    </td>

                    <td>
                        <input type="number"
                               name="rules[${index}][points]"
                               value="0"
                               class="form-control text-center"
                               required>
                    </td>

                    <td>
                        <input type="number"
                               name="rules[${index}][position]"
                               value="0"
                               class="form-control text-center js-rule-position">
                    </td>

                    <td class="text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger js-remove-rule">
                            Supprimer
                        </button>
                    </td>
                </tr>
            `;
        }

        addRuleButton.addEventListener('click', function () {
            if (addRuleButton.disabled) {
                return;
            }

            tableBody.insertAdjacentHTML('beforeend', newRuleRowHtml(nextRuleIndex));
            nextRuleIndex++;

            refreshRulePositions();
            refreshSelects();
        });

        tableBody.addEventListener('change', function (event) {
            if (! event.target.classList.contains('js-rule-code')) {
                return;
            }

            fillLabelFromCode(event.target);
            refreshSelects();
        });

        tableBody.addEventListener('click', function (event) {
            if (! event.target.classList.contains('js-remove-rule')) {
                return;
            }

            event.target.closest('[data-rule-row]').remove();

            refreshRulePositions();
            refreshSelects();
        });

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                ruleCodeSelects().forEach(function (select) {
                    select.value = '';
                });

                document.querySelectorAll('.js-rule-label').forEach(function (input) {
                    input.value = '';
                });

                refreshRulePositions();
                refreshSelects();
            });
        }

        refreshSelects();
    });
</script>

@endsection
