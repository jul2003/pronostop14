@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.edit', $season),
    'label' => 'Retour à la saison',
])

<div class="mb-4">
    <a href="{{ route('admin.seasons.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux saisons
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Barème — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Modifie les points attribués pour cette saison.
    </p>
</div>

<div class="rugby-card p-4">
    <form method="POST" action="{{ route('admin.seasons.scoring.update', $season) }}">
        @csrf
        @method('PUT')

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Règle</th>
                        <th class="text-center" style="width: 160px;">Points</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rules as $rule)
                        <tr>
                            <td class="fw-bold">
                                {{ $rule->label }}
                            </td>

                            <td>
                                <input type="number"
                                       name="rules[{{ $rule->id }}][points]"
                                       value="{{ old("rules.$rule->id.points", $rule->points) }}"
                                       class="form-control text-center"
                                       required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
            Enregistrer le barème
        </button>
    </form>
</div>

@endsection
