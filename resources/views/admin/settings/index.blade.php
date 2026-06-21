@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link')

<div class="mb-4">

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Barèmes & journées
    </h2>

    <p class="text-muted mb-0">
        Gère les barèmes globaux et les associations de barèmes pour les journées.
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
                        Barèmes de journées
                    </h3>

                    <p class="text-muted mb-0">
                        Modèles de barèmes utilisés pour les journées, matchs de phase finale et barrages.
                    </p>
                </div>

                <a href="{{ route('admin.settings.scoring-profiles.create', ['category' => 'journee']) }}"
                   class="btn btn-warning rounded-pill fw-bold px-4">
                    + Créer un barème
                </a>
            </div>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                @forelse($profiles as $profile)
                    <div class="border rounded-4 p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="fw-bold">
                                        {{ $profile->name }}
                                    </div>

                                    <span class="badge rounded-pill text-bg-primary">
                                        Journée
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

                            <a href="{{ route('admin.settings.scoring-profiles.edit', ['profile' => $profile]) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill">
                                Modifier
                            </a>
                        </div>

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
                @empty
                    <div class="alert alert-warning mb-0">
                        Aucun barème de journée n’est encore configuré.
                    </div>
                @endforelse

                @if($profiles->isNotEmpty())
                    <div class="mt-4">
                        <button class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer les points
                        </button>
                    </div>
                @endif
            </form>
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
