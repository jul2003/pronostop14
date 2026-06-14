@extends('layouts.pronos')

@section('content')

@php
    $isEdit = $profile !== null;

    $rules = old('rules');

    if ($rules === null) {
        $rules = $isEdit
            ? $profile->rules->map(fn ($rule) => [
                'code' => $rule->code,
                'label' => $rule->label,
                'points' => $rule->points,
                'position' => $rule->position,
            ])->toArray()
            : [
                ['code' => '', 'label' => '', 'points' => 0, 'position' => 1],
            ];
    }
@endphp

<div class="mb-4">
    <a href="{{ route('admin.settings.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux paramètres
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        {{ $isEdit ? 'Modifier le barème' : 'Créer un barème' }}
    </h2>

    <p class="text-muted mb-0">
        Définis les règles de points qui pourront être associées aux types de journées ou aux pronostics avant-saison.
    </p>
</div>

<form method="POST"
      action="{{ $isEdit
            ? route('admin.settings.scoring-profiles.update', $profile)
            : route('admin.settings.scoring-profiles.store') }}">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    <div class="rugby-card p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">
            Informations générales
        </h3>

        <div class="row g-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Code
                </label>

                <input type="text"
                       name="code"
                       value="{{ old('code', $profile?->code) }}"
                       class="form-control"
                       @disabled($isEdit)
                       required>

                @if($isEdit)
                    <div class="form-text">
                        Le code ne peut pas être modifié après création.
                    </div>
                @else
                    <div class="form-text">
                        Exemple : match_dom_ext, match_neutre, preseason_champion.
                    </div>
                @endif
            </div>

            <div class="col-md-5">
                <label class="form-label fw-bold">
                    Nom
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $profile?->name) }}"
                       class="form-control"
                       required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">
                    Position
                </label>

                <input type="number"
                       name="position"
                       value="{{ old('position', $profile?->position ?? 0) }}"
                       class="form-control text-center">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">
                    Description
                </label>

                <textarea name="description"
                          rows="3"
                          class="form-control">{{ old('description', $profile?->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="rugby-card p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h3 class="h5 fw-bold mb-0">
                Règles du barème
            </h3>

            <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-pill"
                    onclick="addRuleRow()">
                + Ajouter une règle
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Libellé</th>
                        <th class="text-center" style="width: 120px;">Points</th>
                        <th class="text-center" style="width: 120px;">Position</th>
                        <th class="text-end" style="width: 90px;">Action</th>
                    </tr>
                </thead>

                <tbody id="rulesRows">
                    @foreach($rules as $index => $rule)
                        <tr>
                            <td>
                                <input type="text"
                                       name="rules[{{ $index }}][code]"
                                       value="{{ $rule['code'] ?? '' }}"
                                       class="form-control"
                                       required>
                            </td>

                            <td>
                                <input type="text"
                                       name="rules[{{ $index }}][label]"
                                       value="{{ $rule['label'] ?? '' }}"
                                       class="form-control"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="rules[{{ $index }}][points]"
                                       value="{{ $rule['points'] ?? 0 }}"
                                       class="form-control text-center"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="rules[{{ $index }}][position]"
                                       value="{{ $rule['position'] ?? ($index + 1) }}"
                                       class="form-control text-center">
                            </td>

                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill"
                                        onclick="removeRuleRow(this)">
                                    ×
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4 mb-0">
            Chaque règle correspond à un élément de calcul : bon résultat, essais exacts, bonus juste, etc.
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.settings.index') }}"
           class="btn btn-outline-secondary rounded-pill fw-bold px-4">
            Annuler
        </a>

        <button type="submit"
                class="btn btn-warning rounded-pill fw-bold px-4">
            {{ $isEdit ? 'Enregistrer le barème' : 'Créer le barème' }}
        </button>
    </div>
</form>

<script>
    let ruleIndex = {{ count($rules) }};

    function addRuleRow() {
        const tbody = document.getElementById('rulesRows');

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                <input type="text"
                       name="rules[${ruleIndex}][code]"
                       class="form-control"
                       required>
            </td>

            <td>
                <input type="text"
                       name="rules[${ruleIndex}][label]"
                       class="form-control"
                       required>
            </td>

            <td>
                <input type="number"
                       name="rules[${ruleIndex}][points]"
                       value="0"
                       class="form-control text-center"
                       required>
            </td>

            <td>
                <input type="number"
                       name="rules[${ruleIndex}][position]"
                       value="${ruleIndex + 1}"
                       class="form-control text-center">
            </td>

            <td class="text-end">
                <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-pill"
                        onclick="removeRuleRow(this)">
                    ×
                </button>
            </td>
        `;

        tbody.appendChild(row);
        ruleIndex++;
    }

    function removeRuleRow(button) {
        button.closest('tr').remove();
    }
</script>

@endsection
