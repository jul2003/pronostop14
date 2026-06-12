@extends('layouts.pronos')

@section('content')

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">

    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Mes pronostics
        </div>

        <h2 class="fw-bold mb-1">
            {{ $journee->name }}
        </h2>

        <div class="text-muted">
            {{ $season->name }}

            @if($journee->prediction_deadline)
                · limite : {{ $journee->prediction_deadline->format('d/m/Y') }}
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">

        <a href="{{ route('pronos.index') }}"
           class="btn btn-outline-primary rounded-pill fw-bold">
            ← Retour aux journées
        </a>

        <a href="{{ route('rankings.journee', [$season, $journee]) }}">
           class="btn btn-outline-primary rounded-pill fw-bold">
            Classement journée
        </a>

    </div>

</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($isLocked)
    <div class="alert alert-warning">
        Les pronostics sont clôturés. Consultation uniquement.
    </div>
@endif

@if($matches->isEmpty())

    <div class="alert alert-info">
        Aucun match disponible pour cette journée.
    </div>

@else

    <form method="POST"
          action="{{ route('pronos.store', [$season, $journee]) }}">
        @csrf

        <div class="row g-4">

            @foreach($matches as $match)

                @php
                    $prono = $match->pronos->first();
                @endphp

                <div class="col-12">

                    <div class="rugby-card p-4">

                        <div class="row align-items-center g-4">

                            <div class="col-lg-5">

                                <div class="d-flex justify-content-between align-items-center">

                                    <div class="fw-bold">
                                        {{ $match->homeClub->name }}
                                    </div>

                                    <div class="match-versus">
                                        VS
                                    </div>

                                    <div class="fw-bold text-end">
                                        {{ $match->awayClub->name }}
                                    </div>

                                </div>

                            </div>

                            <div class="col-lg-7">

                                <div class="row g-2">

                                    <div class="col-6 col-md-3">

                                        <label class="form-label small fw-bold text-muted">
                                            Résultat
                                        </label>

                                        <select
                                            name="pronos[{{ $match->id }}][predicted_result]"
                                            class="form-select"
                                            required
                                            @disabled($isLocked)
                                        >
                                            <option value="" @selected($prono?->predicted_result === null)></option>

                                            <option value="v" @selected($prono?->predicted_result === 'v')>
                                                v
                                            </option>

                                            <option value="n" @selected($prono?->predicted_result === 'n')>
                                                n
                                            </option>

                                            <option value="d" @selected($prono?->predicted_result === 'd')>
                                                d
                                            </option>
                                        </select>

                                    </div>

                                    <div class="col-6 col-md-3">

                                        <label class="form-label small fw-bold text-muted">
                                            Essais
                                        </label>

                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            name="pronos[{{ $match->id }}][predicted_tries]"
                                            value="{{ $prono?->predicted_tries }}"
                                            class="form-control text-center"
                                            required
                                            @disabled($isLocked)
                                        >

                                    </div>

                                    <div class="col-6 col-md-3">

                                        <label class="form-label small fw-bold text-muted">
                                            Bonus dom.
                                        </label>

                                        <select
                                            name="pronos[{{ $match->id }}][predicted_home_bonus]"
                                            class="form-select"
                                            @disabled($isLocked)
                                        >
                                            <option value=""></option>

                                            <option value="o"
                                                @selected($prono?->predicted_home_bonus === 'o')>
                                                o
                                            </option>

                                            <option value="-"
                                                @selected($prono?->predicted_home_bonus === '-')>
                                                -
                                            </option>

                                            <option value="d"
                                                @selected($prono?->predicted_home_bonus === 'd')>
                                                d
                                            </option>
                                        </select>

                                    </div>

                                    <div class="col-6 col-md-3">

                                        <label class="form-label small fw-bold text-muted">
                                            Bonus ext.
                                        </label>

                                        <select
                                            name="pronos[{{ $match->id }}][predicted_away_bonus]"
                                            class="form-select"
                                            @disabled($isLocked)
                                        >
                                            <option value=""></option>

                                            <option value="o"
                                                @selected($prono?->predicted_away_bonus === 'o')>
                                                o
                                            </option>

                                            <option value="-"
                                                @selected($prono?->predicted_away_bonus === '-')>
                                                -
                                            </option>

                                            <option value="d"
                                                @selected($prono?->predicted_away_bonus === 'd')>
                                                d
                                            </option>
                                        </select>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

        @unless($isLocked)

            <div class="mt-4">

                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer mes pronostics
                </button>

            </div>

        @endunless

    </form>

@endif

@endsection
