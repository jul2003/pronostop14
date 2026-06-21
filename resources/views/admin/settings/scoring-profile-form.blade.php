@extends('layouts.pronos')

@section('content')

@php
    $isEdit = $profile !== null;
    $returnTo = $returnTo ?? request('return_to');
    $selectedCategory = old('category', $profile->category ?? $defaultCategory ?? 'journee');

    $backRoute = $returnTo === 'preseason'
        ? route('admin.settings.preseason')
        : route('admin.settings.index');
@endphp

<div class="mb-4">
    <a href="{{ $backRoute }}"
       class="text-decoration-none fw-bold">
        ← Retour aux paramètres
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        {{ $isEdit ? 'Modifier un barème' : 'Créer un barème' }}
    </h2>

    <p class="text-muted mb-0">
        Définis le nom, la catégorie et les règles de points du barème.
    </p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST"
      action="{{ $isEdit
            ? route('admin.settings.scoring-profiles.update', $profile)
            : route('admin.settings.scoring-profiles.store') }}">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    <input type="hidden" name="return_to" value="{{ $returnTo }}">

    <div class="rugby-card p-4 mb-4">
        <h3 class="h5 fw-bold mb-3">
            Informations du barème
        </h3>

        <div class="row g-3">
            @if(! $isEdit)
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Code
                    </label>

                    <input type="text"
                           name="code"
                           value="{{ old('code', $profile->code ?? '') }}"
                           class="form-control"
                           placeholder="preseason_champion_top14"
                           required>

                    <div class="form-text">
                        Identifiant technique unique. Exemple : preseason_champion_top14.
                    </div>
                </div>
            @else
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Code
                    </label>

                    <input type="text"
                           value="{{ $profile->code }}"
                           class="form-control"
                           disabled>

                    <div class="form-text">
                        Le code ne se modifie pas après création.
                    </div>
                </div>
            @endif

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Catégorie
                </label>

                <select name="category"
                        class="form-select"
                        required>
                    <option value="journee" @selected($selectedCategory === 'journee')>
                        Journée
                    </option>

                    <option value="preseason" @selected($selectedCategory === 'preseason')>
                        Avant-saison
                    </option>
                </select>

                <div class="form-text">
                    Permet de filtrer les barèmes dans les bons écrans d’administration.
                </div>
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold">
                    Nom
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $profile->name ?? '') }}"
                       class="form-control"
                       placeholder="Avant-saison — Champion TOP 14"
                       required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Position
                </label>

                <input type="number"
                       name="position"
                       value="{{ old('position', $profile->position ?? 0) }}"
                       class="form-control text-center">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">
                    Description
                </label>

                <textarea name="description"
                          class="form-control"
                          rows="3">{{ old('description', $profile->description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="rugby-card p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h3 class="h5 fw-bold mb-1">
                    Règles
                </h3>

                <p class="text-muted mb-0">
                    Pour les barèmes simples, crée une règle avec le code <strong>correct</strong>.
                </p>
            </div>
        </div>

        <div id="rulesList">
            @php
                $rules = old('rules');

                if ($rules === null && $profile) {
                    $rules = $profile->rules->map(function ($rule) {
                        return [
                            'code' => $rule->code,
                            'label' => $rule->label,
                            'points' => $rule->points,
                            'position' => $rule->position,
                        ];
                    })->toArray();
                }

                if (empty($rules)) {
                    $rules = [
                        [
                            'code' => 'correct',
                            'label' => 'Bonne réponse',
                            'points' => 0,
                            'position' => 10,
                        ],
                    ];
                }
            @endphp

            @foreach($rules as $index => $rule)
                <div class="border rounded-4 p-3 mb-3 rule-item">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                Code
                            </label>

                            <input type="text"
                                   name="rules[{{ $index }}][code]"
                                   value="{{ $rule['code'] ?? '' }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Libellé
                            </label>

                            <input type="text"
                                   name="rules[{{ $index }}][label]"
                                   value="{{ $rule['label'] ?? '' }}"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                Points
                            </label>

                            <input type="number"
                                   name="rules[{{ $index }}][points]"
                                   value="{{ $rule['points'] ?? 0 }}"
                                   class="form-control text-center"
                                   required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                Position
                            </label>

                            <input type="number"
                                   name="rules[{{ $index }}][position]"
                                   value="{{ $rule['position'] ?? 0 }}"
                                   class="form-control text-center">
                        </div>

                        <div class="col-md-1">
                            <button type="button"
                                    class="btn btn-outline-danger rounded-pill w-100"
                                    onclick="removeRule(this)">
                                ×
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button"
                class="btn btn-outline-primary rounded-pill fw-bold px-4"
                onclick="addRule()">
            + Ajouter une règle
        </button>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-warning rounded-pill fw-bold px-4">
            {{ $isEdit ? 'Enregistrer le barème' : 'Créer le barème' }}
        </button>

        <a href="{{ $backRoute }}"
           class="btn btn-outline-secondary rounded-pill fw-bold px-4">
            Annuler
        </a>
    </div>
</form>

@endsection

@push('scripts')
<script>
    let ruleIndex = {{ count($rules) }};

    function addRule() {
        const list = document.getElementById('rulesList');

        const html = `
            <div class="border rounded-4 p-3 mb-3 rule-item">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            Code
                        </label>

                        <input type="text"
                               name="rules[${ruleIndex}][code]"
                               value="correct"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Libellé
                        </label>

                        <input type="text"
                               name="rules[${ruleIndex}][label]"
                               value="Bonne réponse"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            Points
                        </label>

                        <input type="number"
                               name="rules[${ruleIndex}][points]"
                               value="0"
                               class="form-control text-center"
                               required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            Position
                        </label>

                        <input type="number"
                               name="rules[${ruleIndex}][position]"
                               value="10"
                               class="form-control text-center">
                    </div>

                    <div class="col-md-1">
                        <button type="button"
                                class="btn btn-outline-danger rounded-pill w-100"
                                onclick="removeRule(this)">
                            ×
                        </button>
                    </div>
                </div>
            </div>
        `;

        list.insertAdjacentHTML('beforeend', html);

        ruleIndex++;
    }

    function removeRule(button) {
        const items = document.querySelectorAll('.rule-item');

        if (items.length <= 1) {
            alert('Un barème doit contenir au moins une règle.');
            return;
        }

        button.closest('.rule-item').remove();
    }
</script>
@endpush
