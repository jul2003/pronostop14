@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.index'),
    'label' => 'Retour aux saisons',
])

<div class="mb-4">

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Barème — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Le barème est consultable uniquement.
        @else
            Modifie les points attribués pour cette saison, profil par profil.
        @endif
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Le barème de cette saison ne peut plus être modifié.
            Pour corriger le barème, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<div class="rugby-card p-4">
    @if($profiles->isEmpty())
        <div class="alert alert-warning mb-0">
            Aucun barème n’a été généré pour cette saison.
        </div>
    @else
        <form method="POST" action="{{ route('admin.seasons.scoring.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="d-flex flex-column gap-4">
                @foreach($profiles as $profile)
                    <div class="border rounded-4 overflow-hidden">
                        <div class="bg-light px-4 py-3 border-bottom">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        {{ $profile->name }}
                                    </h5>

                                    @if($profile->description)
                                        <div class="text-muted small">
                                            {{ $profile->description }}
                                        </div>
                                    @endif
                                </div>

                                <div class="text-muted small">
                                    Code : {{ $profile->code }}
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Règle</th>
                                        <th class="text-center" style="width: 160px;">
                                            Points
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($profile->rules as $rule)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">
                                                    {{ $rule->label }}
                                                </div>

                                                <div class="text-muted small">
                                                    {{ $rule->code }}
                                                </div>
                                            </td>

                                            <td>
                                                <input type="number"
                                                       name="rules[{{ $rule->id }}][points]"
                                                       value="{{ old("rules.$rule->id.points", $rule->points) }}"
                                                       class="form-control text-center"
                                                       required
                                                       @disabled($season->is_locked)>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            @unless($season->is_locked)
                <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
                    Enregistrer le barème
                </button>
            @endunless
        </form>
    @endif
</div>

@endsection
