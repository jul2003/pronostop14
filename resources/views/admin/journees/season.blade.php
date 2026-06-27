@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.edit', $season),
    'label' => 'Retour à la saison',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Journées — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Gère les journées, matchs, résultats et paramètres avant-saison de cette saison.
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

@if($journees->isEmpty())

    <div class="rugby-card p-4">
        <div class="alert alert-info">
            Aucune journée n’a encore été générée pour cette saison.
        </div>

        <form method="POST"
              action="{{ route('admin.seasons.generateJournees', $season) }}">
            @csrf

            <button type="submit"
                    class="btn btn-warning rounded-pill fw-bold px-4">
                Générer les journées
            </button>
        </form>
    </div>

@else

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;">N°</th>
                        <th>Journée</th>
                        <th>Type</th>
                        <th class="text-center">Début</th>
                        <th class="text-center">Date limite</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($journees as $journee)
                        <tr>
                            <td class="fw-bold">
                                {{ $journee->number }}
                            </td>

                            <td>
                                <div class="fw-bold">
                                    {{ $journee->name }}
                                </div>

                                <div class="text-muted small">
                                    {{ $journee->slug }}
                                </div>
                            </td>

                            <td>
                                {{ $journee->type_label }}
                            </td>

                            <td class="text-center">
                                @if($journee->starts_at)
                                    {{ $journee->starts_at->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">
                                        Non défini
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($journee->prediction_deadline)
                                    {{ $journee->prediction_deadline->format('d/m/Y') }}
                                @else
                                    <span class="badge bg-danger">
                                        Manquante
                                    </span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.seasons.journees.edit', [$season, $journee]) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill fw-bold">
                                        Modifier
                                    </a>

                                    @if($journee->type === 'preseason')
                                        <a href="{{ route('admin.seasons.preseason.edit', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                                            Questions avant-saison
                                        </a>

                                        <a href="{{ route('admin.seasons.preseason-results.edit', $season) }}"
                                           class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                            Résultats avant-saison
                                        </a>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                                            Matchs
                                        </a>

                                        <a href="{{ route('admin.seasons.journees.results', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-warning rounded-pill fw-bold">
                                            Résultats
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endif

@endsection
