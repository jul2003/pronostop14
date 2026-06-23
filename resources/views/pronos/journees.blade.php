@extends('layouts.pronos')

@section('content')

@if(! $season)
    <div class="alert alert-info">
        Aucune saison active disponible pour tes pronostics.
    </div>
@endif

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Pronostics
    </div>

    <h2 class="fw-bold mb-1">
        Pronostics à saisir
    </h2>

    <p class="text-muted mb-0">
        Retrouve ici les pronostics encore ouverts, à saisir ou à modifier.
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
                    @if($journee->type === 'preseason')
                        Questions avant-saison
                    @else
                        {{ $journee->matches_count }} match(s)
                    @endif
                </div>

                @if($journee->type === 'preseason')
                    @if($preseasonDeadline)
                        <div class="small text-secondary mb-3">
                            Limite :
                            {{ $preseasonDeadline->format('d/m/Y') }}
                        </div>
                    @endif
                @elseif($journee->prediction_deadline)
                    <div class="small text-secondary mb-3">
                        Limite :
                        {{ $journee->prediction_deadline->format('d/m/Y') }}
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('pronos.show', [$journee->season, $journee]) }}"
                       class="btn btn-primary rounded-pill">
                        Voir la journée
                    </a>
                </div>

            </div>

        </div>

    @empty

        <div class="col-12">
            <div class="alert alert-info">
                Aucun pronostic ouvert pour le moment.
            </div>
        </div>

    @endforelse

</div>

@endsection
