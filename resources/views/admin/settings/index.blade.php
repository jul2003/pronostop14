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
        Paramètres généraux
    </h2>

    <p class="text-muted mb-0">
        Modèles globaux utilisés lors de la création d’une nouvelle saison.
    </p>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">

        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Barèmes globaux
                </h3>
                <a href="{{ route('admin.settings.scoring-profiles.create') }}"
                class="btn btn-warning rounded-pill fw-bold px-4 mb-3">
                    + Créer un barème
                </a>

                @forelse($profiles as $profile)
                    <div class="border rounded-4 p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="fw-bold">
                                    {{ $profile->name }}
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
                        <a href="{{ route('admin.settings.scoring-profiles.edit', $profile) }}"
                        class="btn btn-sm btn-outline-primary rounded-pill">
                            Modifier
                        </a>
                    </div>
                @empty
                    <div class="alert alert-warning mb-0">
                        Aucun barème global n’est encore configuré.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Association type de journée → barème
                </h3>

                @php
                    $journeeTypes = [
                        'preseason' => 'Avant-saison',
                        'regular' => 'Journée régulière',
                        'prod2_final' => 'Finale PRO D2',
                        'access_match' => 'Barrage TOP 14 / PRO D2',
                        'top14_playoff' => 'Barrages TOP 14',
                        'top14_semifinal' => 'Demi-finales TOP 14',
                        'top14_final' => 'Finale TOP 14',
                    ];
                @endphp

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
            </div>
        </div>

        <div class="col-12">
            <div class="rugby-card p-4">
                <h3 class="h5 fw-bold mb-3">
                    Questions avant-saison
                </h3>

                @if($preseasonTemplates->isEmpty())
                    <div class="alert alert-warning mb-0">
                        Aucune question avant-saison n’est encore configurée.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Libellé</th>
                                    <th>Type de réponse</th>
                                    <th>Barème</th>
                                    <th class="text-center">Position</th>
                                    <th class="text-center">Actif</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($preseasonTemplates as $template)
                                    <tr>
                                        <td>
                                            <input type="text"
                                                   name="preseason[{{ $template->id }}][label]"
                                                   value="{{ $template->label }}"
                                                   class="form-control">
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][answer_type]"
                                                    class="form-select">
                                                <option value="top14_club" @selected($template->answer_type === 'top14_club')>
                                                    Club TOP 14
                                                </option>

                                                <option value="prod2_club" @selected($template->answer_type === 'prod2_club')>
                                                    Club PRO D2
                                                </option>

                                                <option value="season_club" @selected($template->answer_type === 'season_club')>
                                                    Club TOP 14 ou PRO D2
                                                </option>

                                                <option value="free_text" @selected($template->answer_type === 'free_text')>
                                                    Champ libre
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <select name="preseason[{{ $template->id }}][scoring_profile_id]"
                                                    class="form-select">
                                                @foreach($profiles as $profile)
                                                    <option value="{{ $profile->id }}"
                                                        @selected($template->scoring_profile_id === $profile->id)>
                                                        {{ $profile->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td style="width: 110px;">
                                            <input type="number"
                                                   name="preseason[{{ $template->id }}][position]"
                                                   value="{{ $template->position }}"
                                                   class="form-control text-center">
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox"
                                                   name="preseason[{{ $template->id }}][is_active]"
                                                   value="1"
                                                   class="form-check-input"
                                                   @checked($template->is_active)>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <div class="mt-4">
        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Enregistrer les paramètres
        </button>
    </div>
</form>

@endsection
