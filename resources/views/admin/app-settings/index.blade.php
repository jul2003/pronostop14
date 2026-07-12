@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Paramètres de l’application
    </h2>

    <p class="text-muted mb-0">
        Réglages fonctionnels globaux utilisés par l’application.
    </p>
</div>

<form method="POST"
      action="{{ route('admin.app-settings.update') }}">
    @csrf
    @method('PUT')

    <div class="rugby-card p-4">
        @if($settings->isEmpty())
            <div class="alert alert-info mb-0">
                Aucun paramètre d’application n’est encore configuré.
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paramètre</th>
                            <th>Description</th>
                            <th class="text-center" style="width: 300px;">Valeur</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($settings as $setting)
                            @php
                                $fieldName = "settings.{$setting->id}";
                                $fieldInputName = "settings[{$setting->id}]";
                                $currentValue = old($fieldName, $setting->typedValue());

                                $colorTextValue = is_string($currentValue)
                                    ? strtoupper($currentValue)
                                    : '#FFFFFF';

                                $colorPickerValue = preg_match('/^#[0-9A-Fa-f]{6}$/', $colorTextValue)
                                    ? $colorTextValue
                                    : '#FFFFFF';
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $setting->label }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ $setting->key }}
                                    </div>
                                </td>

                                <td class="text-muted">
                                    {{ $setting->description }}
                                </td>

                                <td>
                                    @if($setting->type === 'boolean')
                                        <div class="form-check d-flex justify-content-center">
                                            <input type="checkbox"
                                                   name="{{ $fieldInputName }}"
                                                   value="1"
                                                   class="form-check-input"
                                                   @checked(old($fieldName, $setting->typedValue()))>
                                        </div>
                                    @elseif($setting->type === 'integer')
                                        <input type="text"
                                               name="{{ $fieldInputName }}"
                                               value="{{ old($fieldName, $setting->typedValue()) }}"
                                               class="form-control text-center"
                                               inputmode="numeric"
                                               pattern="[0-9]+"
                                               required>
                                    @elseif($setting->type === 'date')
                                        <div class="input-group">
                                            <input type="date"
                                                   name="{{ $fieldInputName }}"
                                                   value="{{ old($fieldName, $setting->typedValue()) }}"
                                                   class="form-control text-center app-date-input">

                                            <button type="button"
                                                    class="btn btn-outline-secondary clear-date-button"
                                                    title="Effacer la date"
                                                    aria-label="Effacer la date">
                                                ×
                                            </button>
                                        </div>
                                    @elseif($setting->type === 'color')
                                        <div class="input-group color-setting-group">
                                            <input type="color"
                                                   value="{{ $colorPickerValue }}"
                                                   class="form-control form-control-color app-color-picker"
                                                   title="Choisir une couleur"
                                                   aria-label="Choisir une couleur">

                                            <input type="text"
                                                   name="{{ $fieldInputName }}"
                                                   value="{{ $colorTextValue }}"
                                                   class="form-control text-center app-color-input"
                                                   maxlength="7"
                                                   pattern="#[0-9A-Fa-f]{6}"
                                                   placeholder="#FFFFFF"
                                                   required>
                                        </div>

                                        <div class="form-text text-center">
                                            Format attendu : #RRGGBB
                                        </div>
                                    @else
                                        <input type="text"
                                               name="{{ $fieldInputName }}"
                                               value="{{ old($fieldName, $setting->typedValue()) }}"
                                               class="form-control">
                                    @endif

                                    @error($fieldName)
                                        <div class="text-danger small mt-1 text-center">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer les paramètres
                </button>
            </div>
        @endif
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const group = button.closest('.input-group');

                if (! group) {
                    return;
                }

                const input = group.querySelector('.app-date-input');

                if (! input) {
                    return;
                }

                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.querySelectorAll('.color-setting-group').forEach(function (group) {
            const picker = group.querySelector('.app-color-picker');
            const input = group.querySelector('.app-color-input');

            if (! picker || ! input) {
                return;
            }

            picker.addEventListener('input', function () {
                input.value = picker.value.toUpperCase();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            input.addEventListener('input', function () {
                const value = input.value.trim().toUpperCase();

                input.value = value;

                if (/^#[0-9A-F]{6}$/.test(value)) {
                    picker.value = value;
                }
            });
        });
    });
</script>

@endsection
