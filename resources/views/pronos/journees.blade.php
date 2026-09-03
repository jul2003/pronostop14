@extends('layouts.pronos')

@section('content')

@php
    $currentAppDateTime = app(\App\Services\AppDateService::class)->now();
@endphp


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

        @php
            $isPreseason =
                $journee->type === 'preseason';

            $journeeHasPassed =
                ! $isPreseason
                && $journee->first_match_at
                && $currentAppDateTime->greaterThanOrEqualTo(
                    $journee->first_match_at
                );

            $mainDeadlineIsOpen =
                ! $isPreseason
                && $journee->first_match_at
                && $currentAppDateTime->lt(
                    $journee->first_match_at
                );

            $openMatches =
                collect();

            $openMatchDeadlineGroups =
                collect();


            if (! $isPreseason) {

                $openMatches =
                    $journee
                        ->matches
                        ->filter(
                            fn ($match) =>
                                ! $match->isPredictionLocked()
                        );


                if ($journeeHasPassed) {

                    $openMatchDeadlineGroups =
                        $openMatches
                            ->filter(
                                fn ($match) =>
                                    $match->effectivePredictionDeadline()
                                    !== null
                            )
                            ->groupBy(
                                fn ($match) =>
                                    $match
                                        ->effectivePredictionDeadline()
                                        ->format('Y-m-d H:i')
                            )
                            ->map(
                                function ($matches) {

                                    $deadline =
                                        $matches
                                            ->first()
                                            ->effectivePredictionDeadline();

                                    return [
                                        'count' =>
                                            $matches->count(),

                                        'deadline' =>
                                            $deadline,
                                    ];
                                }
                            )
                            ->sortBy(
                                fn ($group) =>
                                    $group['deadline']->timestamp
                            )
                            ->values();

                }

            }


            $openMatchesCount =
                $openMatches->count();

            $matchesCount =
                (int) $journee->matches_count;

            $submittedPronosCount =
                (int) $journee->user_pronos_count;


            /*
             * Date effective d'affichage du récapitulatif.
             *
             * predictionRecapVisibleAt() tient déjà compte
             * du paramètre global :
             *
             * - option OFF :
             *   retourne first_match_at
             *
             * - option ON + date anticipée :
             *   retourne predictions_visible_at
             *
             * - option ON + date vide :
             *   retourne first_match_at
             */
            $predictionRecapVisibleAt =
                ! $isPreseason
                    ? $journee->predictionRecapVisibleAt()
                    : null;


            /*
             * On affiche l'information uniquement
             * lorsqu'il s'agit réellement
             * d'un affichage anticipé.
             */
            $hasEarlyPredictionRecap =
                ! $isPreseason
                && $journee->first_match_at
                && $predictionRecapVisibleAt
                && $predictionRecapVisibleAt->lt(
                    $journee->first_match_at
                );
        @endphp


        <div class="col-md-6 col-xl-4">

            <div class="rugby-card p-4 h-100">

                <div class="text-uppercase text-primary fw-bold small">
                    {{ $journee->season->name }}
                </div>


                <h3 class="h5 fw-bold mt-2">
                    {{ $journee->name }}
                </h3>


                <div class="text-muted mb-2">

                    @if($isPreseason)

                        Questions avant-saison

                    @else

                        {{ $journee->matches_count }} match(s)

                    @endif

                </div>


                {{-- ÉTAT DE SAISIE --}}

                @if(! $isPreseason)

                    <div class="mb-3">

                        @if($submittedPronosCount === 0)

                            <span class="badge rounded-pill text-bg-secondary">
                                Non saisi
                            </span>

                        @elseif($submittedPronosCount >= $matchesCount)

                            <span class="badge rounded-pill text-bg-success">
                                Pronostics saisis
                            </span>

                        @else

                            <span class="badge rounded-pill text-bg-warning">

                                {{ $submittedPronosCount }}
                                /
                                {{ $matchesCount }}

                                pronostics saisis

                            </span>

                        @endif

                    </div>

                @endif


                {{-- DEADLINE --}}

                @if($isPreseason)

                    @if($preseasonDeadline)

                        <div class="small text-secondary mb-3">

                            Prono ouvert jusqu’au :

                            <strong>
                                {{ $preseasonDeadline->format('d/m/Y H:i') }}
                            </strong>

                        </div>

                    @endif


                @elseif($mainDeadlineIsOpen)

                    <div class="small text-secondary mb-3">

                        Prono ouvert jusqu’au :

                        <strong>
                            {{ $journee->first_match_at->format('d/m/Y H:i') }}
                        </strong>

                    </div>


                @elseif($openMatchDeadlineGroups->isNotEmpty())

                    <div class="mb-3">

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                            <span class="badge rounded-pill text-bg-secondary">
                                Journée passée
                            </span>

                            <span class="small fw-bold text-secondary">

                                {{ $openMatchesCount }}

                                {{ $openMatchesCount > 1
                                    ? 'matchs'
                                    : 'match'
                                }}

                                à pronostiquer

                            </span>

                        </div>


                        <div class="small text-secondary">

                            @foreach($openMatchDeadlineGroups as $group)

                                <div>

                                    {{ $group['count'] }}

                                    {{ $group['count'] > 1
                                        ? 'matchs'
                                        : 'match'
                                    }}

                                    avant le

                                    {{ $group['deadline']->format('d/m/Y H:i') }}

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif


                {{-- AFFICHAGE ANTICIPÉ DU RÉCAPITULATIF --}}

                @if($hasEarlyPredictionRecap)

                    <div class="alert alert-info py-2 px-3 mb-3">

                        <div class="small">

                            <div class="fw-bold mb-1">
                                👀 Affichage des pronostics
                            </div>


                            @if($journee->isPredictionRecapVisible())

                                Le récapitulatif est visible depuis le

                                <strong>
                                    {{ $predictionRecapVisibleAt->format('d/m/Y à H:i') }}
                                </strong>.

                            @else

                                Le récapitulatif sera visible à partir du

                                <strong>
                                    {{ $predictionRecapVisibleAt->format('d/m/Y à H:i') }}
                                </strong>.

                            @endif

                        </div>

                    </div>

                @endif


                {{-- ACTION --}}

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
