@extends('layouts.pronos')

@section('content')

@if(! $season)
    <div class="alert alert-info">
        Aucune saison active pour le moment.
    </div>
@endif

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Pronostics
    </div>

    <h2 class="fw-bold mb-1">
        Journées disponibles
    </h2>

    <p class="text-muted mb-0">
        Sélectionne une journée pour saisir ou consulter tes pronostics.
    </p>
</div>

<div class="row g-4">

    @forelse($journees as $journee)

        <div class="col-md-6 col-xl-4">

            <div class="rugby-card p-4 h-100">

                <div class="text-uppercase text-primary fw-bold small">
                    {{ $journee->season->name }}
                </div>

                <h3 class="h5 fw-bold mt-2">
                    {{ $journee->name }}
                </h3>

                <div class="text-muted mb-2">
                    {{ $journee->matches_count }} match(s)
                </div>

                @if($journee->prediction_deadline)
                    <div class="small text-secondary mb-3">
                        Limite :
                        {{ $journee->prediction_deadline->format('d/m/Y') }}
                    </div>
                @endif

                <a href="{{ route('pronos.show', [$journee->season, $journee]) }}"
                   class="btn btn-primary rounded-pill">
                    Voir la journée
                </a>

                @if($journee->isLocked())
                    <a href="{{ route('journees.results', [$journee->season, $journee]) }}"
                    class="btn btn-outline-primary rounded-pill">
                        Voir les résultats
                    </a>
                @endif

            </div>

        </div>

    @empty

        <div class="col-12">
            <div class="alert alert-info">
                Aucune journée disponible.
            </div>
        </div>

    @endforelse

</div>

@endsection
