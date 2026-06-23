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
                            <th class="text-center" style="width: 260px;">Valeur</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($settings as $setting)
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
                                                   name="settings[{{ $setting->id }}]"
                                                   value="1"
                                                   class="form-check-input"
                                                   @checked(old("settings.{$setting->id}", $setting->typedValue()))>
                                        </div>
                                    @elseif($setting->type === 'integer')
                                        <input type="text"
                                               name="settings[{{ $setting->id }}]"
                                               value="{{ old("settings.{$setting->id}", $setting->typedValue()) }}"
                                               class="form-control text-center"
                                               inputmode="numeric"
                                               pattern="[0-9]+"
                                               required>
                                    @elseif($setting->type === 'date')
                                        <div class="input-group">
                                            <input type="date"
                                                   name="settings[{{ $setting->id }}]"
                                                   value="{{ old("settings.{$setting->id}", $setting->typedValue()) }}"
                                                   class="form-control text-center app-date-input">

                                            <button type="button"
                                                    class="btn btn-outline-secondary clear-date-button"
                                                    title="Effacer la date"
                                                    aria-label="Effacer la date">
                                                ×
                                            </button>
                                        </div>
                                    @else
                                        <input type="text"
                                               name="settings[{{ $setting->id }}]"
                                               value="{{ old("settings.{$setting->id}", $setting->typedValue()) }}"
                                               class="form-control">
                                    @endif
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
    });
</script>

@endsection
