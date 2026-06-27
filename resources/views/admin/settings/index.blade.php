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
        Barèmes & journées
    </h2>

    <p class="text-muted mb-0">
        Gère les barèmes de journées et les associations par type de journée.
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
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Barèmes de journées
                    </h3>

                    <p class="text-muted mb-0">
                        Modèles utilisés pour les journées régulières, barrages, demi-finales et finales.
                    </p>
                </div>

                <a href="{{ route('admin.settings.scoring-profiles.create', [
                        'category' => 'journee',
                        'return_to' => 'settings',
                    ]) }}"
                   class="btn btn-warning rounded-pill fw-bold px-4">
                    + Créer un barème
                </a>
            </div>

            @if($profiles->isEmpty())
                <div class="alert alert-warning mb-0">
                    Aucun barème de journée n’est encore configuré.
                </div>
            @else
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="d-grid gap-3">
                        @foreach($profiles as $profile)
                            <div class="border rounded-4 bg-white overflow-hidden">
                                <div class="p-3 p-md-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <h4 class="h5 fw-bold mb-0">
                                                    {{ $profile->name }}
                                                </h4>

                                                <span class="badge rounded-pill text-bg-primary">
                                                    Journée
                                                </span>

                                                <span class="badge rounded-pill text-bg-light border text-dark">
                                                    {{ $profile->rules->count() }} règle(s)
                                                </span>
                                            </div>

                                            <div class="text-muted small">
                                                {{ $profile->code }}
                                            </div>

                                            @if($profile->description)
                                                <div class="text-muted small mt-1">
                                                    {{ $profile->description }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary rounded-pill"
                                                    onclick="togglePanel('rules_panel_{{ $profile->id }}', 'rules_icon_{{ $profile->id }}')">
                                                <span>
                                                    Modifier les règles
                                                </span>

                                                <span id="rules_icon_{{ $profile->id }}">
                                                    +
                                                </span>
                                            </button>

                                            <a href="{{ route('admin.settings.scoring-profiles.edit', [
                                                    'profile' => $profile,
                                                    'return_to' => 'settings',
                                                ]) }}"
                                               class="btn btn-sm btn-outline-secondary rounded-pill">
                                                Modifier le barème
                                            </a>
                                        </div>
                                    </div>

                                    @if($profile->rules->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            @foreach($profile->rules as $rule)
                                                <span class="badge rounded-pill text-bg-light border text-dark px-3 py-2">
                                                    {{ $rule->label }} : {{ $rule->points }} pts
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div id="rules_panel_{{ $profile->id }}"
                                     class="border-top p-3 p-md-4 bg-light d-none">
                                    @if($profile->rules->isEmpty())
                                        <div class="alert alert-info mb-0">
                                            Aucune règle dans ce barème.
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Règle</th>
                                                        <th class="text-center">Code</th>
                                                        <th class="text-center" style="width: 140px;">
                                                            Points
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach($profile->rules as $rule)
                                                        <tr>
                                                            <td class="fw-bold">
                                                                {{ $rule->label }}
                                                            </td>

                                                            <td class="text-center text-muted">
                                                                {{ $rule->code }}
                                                            </td>

                                                            <td>
                                                                <input type="number"
                                                                       name="rules[{{ $rule->id }}]"
                                                                       value="{{ $rule->points }}"
                                                                       class="form-control form-control-sm text-center">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer les points
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <h3 class="h5 fw-bold mb-1">
                Association type de journée → barème
            </h3>

            <p class="text-muted mb-3">
                Associe un barème par défaut aux différents types de journées sportives.
            </p>

            @php
                $journeeTypes = [
                    'regular' => 'Journée régulière',
                    'prod2_final' => 'Finale PRO D2',
                    'access_match' => 'Barrage TOP 14 / PRO D2',
                    'top14_playoff' => 'Barrages TOP 14',
                    'top14_semifinal' => 'Demi-finales TOP 14',
                    'top14_final' => 'Finale TOP 14',
                ];
            @endphp

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type de journée</th>
                                <th>Barème associé</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($journeeTypes as $type => $label)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $label }}

                                        <div class="text-muted small">
                                            {{ $type }}
                                        </div>
                                    </td>

                                    <td>
                                        <select name="journee_profiles[{{ $type }}]"
                                                class="form-select">
                                            <option value="">
                                                Aucun barème
                                            </option>

                                            @foreach($profiles as $profile)
                                                <option value="{{ $profile->id }}"
                                                    @selected(optional($journeeMappings->get($type))->scoring_profile_id === $profile->id)>
                                                    {{ $profile->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <button class="btn btn-warning rounded-pill fw-bold px-4">
                        Enregistrer les associations
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    function togglePanel(panelId, iconId) {
        const panel = document.getElementById(panelId);
        const icon = document.getElementById(iconId);

        if (! panel) {
            return;
        }

        panel.classList.toggle('d-none');

        if (icon) {
            icon.textContent = panel.classList.contains('d-none') ? '+' : '−';
        }
    }
</script>
@endpush
