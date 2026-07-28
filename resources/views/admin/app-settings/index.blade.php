@extends('layouts.pronos')

@section('content')

@php
    $defaultFirstMatchTime = optional($settings->firstWhere('key', 'default_first_match_time'))->typedValue() ?: '12:00';

    $dateTimeParts = function ($value) {
        if (! $value) {
            return [
                'date' => '',
                'time' => '',
                'hidden' => '',
            ];
        }

        try {
            $date = \Carbon\Carbon::parse($value);

            return [
                'date' => $date->format('Y-m-d'),
                'time' => $date->format('H:i'),
                'hidden' => $date->format('Y-m-d H:i'),
            ];
        } catch (\Throwable $exception) {
            return [
                'date' => '',
                'time' => '',
                'hidden' => '',
            ];
        }
    };
@endphp

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

    @if($settings->isNotEmpty())
        <div class="sticky-form-actions">
            <div class="sticky-form-actions-inner">
                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer les paramètres
                </button>
            </div>
        </div>
    @endif

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
                            <th class="text-center" style="width: 360px;">Valeur</th>
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

                                $dateTimeValue = $dateTimeParts($currentValue);
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
                                    @elseif($setting->type === 'datetime' && $setting->key === 'simulated_app_date')
                                        <div class="simulated-datetime-group"
                                             data-default-time="{{ $defaultFirstMatchTime }}">
                                            <input type="hidden"
                                                   name="{{ $fieldInputName }}"
                                                   value="{{ $dateTimeValue['hidden'] }}"
                                                   class="simulated-datetime-hidden">

                                            <div class="input-group mb-2">
                                                <input type="date"
                                                       value="{{ $dateTimeValue['date'] }}"
                                                       class="form-control text-center simulated-date-input">

                                                <input type="time"
                                                       value="{{ $dateTimeValue['time'] }}"
                                                       class="form-control text-center simulated-time-input">

                                                <button type="button"
                                                        class="btn btn-outline-secondary clear-date-button"
                                                        title="Effacer la date simulée"
                                                        aria-label="Effacer la date simulée">
                                                    ×
                                                </button>
                                            </div>

                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary fw-bold apply-simulated-offset"
                                                        data-offset-minutes="-5">
                                                    Heure par défaut -5’
                                                </button>

                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary fw-bold apply-simulated-offset"
                                                        data-offset-minutes="5">
                                                    Heure par défaut +5’
                                                </button>
                                            </div>
                                        </div>
                                    @elseif($setting->type === 'datetime')
                                        <input type="datetime-local"
                                               name="{{ $fieldInputName }}"
                                               value="{{ $dateTimeValue['date'] && $dateTimeValue['time'] ? $dateTimeValue['date'].'T'.$dateTimeValue['time'] : '' }}"
                                               class="form-control text-center">
                                    @elseif($setting->type === 'time')
                                        <input type="time"
                                               name="{{ $fieldInputName }}"
                                               value="{{ old($fieldName, $setting->typedValue()) }}"
                                               class="form-control text-center {{ $setting->key === 'default_first_match_time' ? 'default-first-match-time-input' : '' }}"
                                               required>
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
        @endif
    </div>
</form>

@endsection

@push('styles')
<style>
    .sticky-form-actions {
        position: sticky;
        top: 0;
        z-index: 1040;
        margin-bottom: 1rem;
        padding: 0.75rem 0;
        background: #ffffff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .sticky-form-actions-inner {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function normalizeDateTimeValue(group) {
            const hidden = group.querySelector('.simulated-datetime-hidden');
            const dateInput = group.querySelector('.simulated-date-input');
            const timeInput = group.querySelector('.simulated-time-input');

            if (!hidden || !dateInput || !timeInput) {
                return;
            }

            if (!dateInput.value) {
                hidden.value = '';
                return;
            }

            hidden.value = dateInput.value + ' ' + (timeInput.value || '00:00');
        }

        function currentDefaultFirstMatchTime(group) {
            const liveDefaultInput = document.querySelector('.default-first-match-time-input');

            if (liveDefaultInput && /^\d{2}:\d{2}$/.test(liveDefaultInput.value)) {
                return liveDefaultInput.value;
            }

            return group.dataset.defaultTime || '12:00';
        }

        function applyDefaultTimeWhenDateIsSelected(group) {
            const dateInput = group.querySelector('.simulated-date-input');
            const timeInput = group.querySelector('.simulated-time-input');

            if (!dateInput || !timeInput) {
                return;
            }

            if (!dateInput.value) {
                return;
            }

            timeInput.value = currentDefaultFirstMatchTime(group);
        }

        function timeWithOffset(defaultTime, offsetMinutes) {
            const parts = defaultTime.split(':');

            if (parts.length !== 2) {
                return defaultTime;
            }

            const date = new Date('2000-01-01T' + defaultTime + ':00');

            if (Number.isNaN(date.getTime())) {
                return defaultTime;
            }

            date.setMinutes(date.getMinutes() + offsetMinutes);

            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return hours + ':' + minutes;
        }

        document.querySelectorAll('.clear-date-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const group = button.closest('.input-group');

                if (!group) {
                    return;
                }

                group.querySelectorAll('input').forEach(function (input) {
                    if (input.type === 'date' || input.type === 'time') {
                        input.value = '';
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                const simulatedGroup = button.closest('.simulated-datetime-group');

                if (simulatedGroup) {
                    normalizeDateTimeValue(simulatedGroup);
                }
            });
        });

        document.querySelectorAll('.simulated-datetime-group').forEach(function (group) {
            const dateInput = group.querySelector('.simulated-date-input');
            const timeInput = group.querySelector('.simulated-time-input');

            if (dateInput) {
                dateInput.addEventListener('change', function () {
                    applyDefaultTimeWhenDateIsSelected(group);
                    normalizeDateTimeValue(group);
                });
            }

            if (timeInput) {
                timeInput.addEventListener('change', function () {
                    normalizeDateTimeValue(group);
                });
            }

            group.querySelectorAll('.apply-simulated-offset').forEach(function (button) {
                button.addEventListener('click', function () {
                    const defaultTime = currentDefaultFirstMatchTime(group);
                    const offsetMinutes = parseInt(button.dataset.offsetMinutes || '0', 10);

                    if (timeInput) {
                        timeInput.value = timeWithOffset(defaultTime, offsetMinutes);
                    }

                    normalizeDateTimeValue(group);
                });
            });

            normalizeDateTimeValue(group);
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
@endpush
