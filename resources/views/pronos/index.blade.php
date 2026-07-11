@extends('layouts.pronos')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Mes pronostics
        </div>

        <h2 class="fw-bold mb-1">
            {{ $journee->name }}
        </h2>

        <p class="text-muted mb-0">
            {{ $season->name }}
            @if($journee->prediction_deadline)
                · limite : {{ $journee->prediction_deadline->format('d/m/Y H:i') }}
            @endif
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('pronos.index') }}"
           class="btn btn-outline-secondary rounded-pill fw-bold px-4">
            ← Retour aux journées
        </a>

        <a href="{{ route('seasons.active.results') }}"
           class="btn btn-outline-primary rounded-pill fw-bold px-4">
            Résultats & points
        </a>

        @if($isLocked)
            <a href="{{ route('rankings.journee', [$season, $journee]) }}"
               class="btn btn-warning rounded-pill fw-bold px-4">
                Classement journée
            </a>
        @endif
    </div>
</div>

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

@if($isLocked)
    <div class="alert alert-info">
        Les pronostics sont clôturés. Consultation uniquement.
        Le classement de la journée est maintenant disponible.
    </div>
@endif

@if($matches->isEmpty())
    <div class="rugby-card p-4">
        <div class="alert alert-info mb-0">
            Aucun match disponible pour cette journée.
        </div>
    </div>
@else
    <form method="POST" action="{{ route('pronos.store', [$season, $journee]) }}">
        @csrf

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center" style="width: 190px;">Résultat</th>
                            <th class="text-center" style="width: 140px;">Essais</th>
                            <th class="text-center" style="width: 150px;">Bonus dom.</th>
                            <th class="text-center" style="width: 150px;">Bonus ext.</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matches as $match)
                            @php
                                $prono = $match->pronos->first();

                                $resultValue = old(
                                    "pronos.{$match->id}.predicted_result",
                                    $prono?->predicted_result
                                );

                                $triesValue = old(
                                    "pronos.{$match->id}.predicted_tries",
                                    $prono?->predicted_tries
                                );

                                $homeBonusValue = old(
                                    "pronos.{$match->id}.predicted_home_bonus",
                                    $prono?->predicted_home_bonus ?: '-'
                                );

                                $awayBonusValue = old(
                                    "pronos.{$match->id}.predicted_away_bonus",
                                    $prono?->predicted_away_bonus ?: '-'
                                );
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $match->homeClub->name }}
                                    </div>

                                    <div class="text-muted small">
                                        contre
                                    </div>

                                    <div class="fw-bold">
                                        {{ $match->awayClub->name }}
                                    </div>
                                </td>

                                <td>
                                    <select name="pronos[{{ $match->id }}][predicted_result]"
                                            class="form-select"
                                            @disabled($isLocked)
                                            required>
                                        <option value="">
                                            Choisir...
                                        </option>

                                        @foreach($journee->resultOptionShortLabels() as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected($resultValue === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input type="number"
                                           min="0"
                                           name="pronos[{{ $match->id }}][predicted_tries]"
                                           value="{{ $triesValue }}"
                                           class="form-control text-center"
                                           @disabled($isLocked)
                                           required>
                                </td>

                                <td>
                                    <select name="pronos[{{ $match->id }}][predicted_home_bonus]"
                                            class="form-select"
                                            @disabled($isLocked)>
                                        @foreach(['o' => 'Offensif', '-' => '-', 'd' => 'Défensif'] as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected($homeBonusValue === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select name="pronos[{{ $match->id }}][predicted_away_bonus]"
                                            class="form-select"
                                            @disabled($isLocked)>
                                        @foreach(['o' => 'Offensif', '-' => '-', 'd' => 'Défensif'] as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected($awayBonusValue === $value)>
                                                {{ $label }}
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

        @unless($isLocked)
            <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
                Enregistrer mes pronostics
            </button>
        @endunless
    </form>
@endif

@endsection
