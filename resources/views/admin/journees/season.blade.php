@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.edit', $season),
    'label' => 'Retour à la saison',
])

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="mt-3 text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Journées — {{ $season->name }}
        </h2>

        <p class="text-muted mb-0">
            Gère les journées de cette saison.
        </p>
    </div>

    @if($journees->isEmpty())
        <form method="POST"
              action="{{ route('admin.seasons.generateJournees', $season) }}">
            @csrf

            <button class="btn btn-warning rounded-pill fw-bold px-4">
                Générer les journées
            </button>
        </form>
    @endif
</div>

@if($journees->isEmpty())
    <div class="alert alert-info">
        Aucune journée générée pour cette saison.
    </div>
@else
    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Journée</th>
                        <th>Type</th>
                        <th class="text-center">Date limite</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($journees as $journee)
                        <tr>
                            <td class="fw-bold">
                                {{ $journee->name }}
                            </td>

                            <td>
                                {{ $journee->type_label }}
                            </td>

                            <td class="text-center">
                                @if($journee->prediction_deadline)
                                    {{ $journee->prediction_deadline->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">Non définie</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.seasons.journees.edit', [$season, $journee]) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Modifier
                                    </a>

                                    @if($journee->type === 'preseason')
                                        <a href="{{ route('admin.seasons.preseason.edit', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Questions avant-saison
                                        </a>
                                    @else
                                        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Matchs
                                        </a>

                                        <a href="{{ route('admin.seasons.journees.results', [$season, $journee]) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
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
