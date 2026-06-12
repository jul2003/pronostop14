@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.seasons.journees', $season) }}"
       class="text-decoration-none fw-bold">
        ← Retour aux journées
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Résultats — {{ $journee->name }}
    </h2>

    <p class="text-muted mb-0">
        Saisie des résultats réels et recalcul automatique des points.
    </p>
</div>

@if($matches->isEmpty())
    <div class="alert alert-info">
        Aucun match pour cette journée.
    </div>
@else
    <form method="POST"
          action="{{ route('admin.seasons.journees.results.store', [$season, $journee]) }}">
        @csrf

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center">Résultat</th>
                            <th class="text-center">Essais</th>
                            <th class="text-center">Bonus dom.</th>
                            <th class="text-center">Bonus ext.</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matches as $match)
                            <tr>
                                <td class="fw-bold">
                                    {{ $match->homeClub->name }}
                                    <span class="text-muted mx-1">—</span>
                                    {{ $match->awayClub->name }}
                                </td>

                                <td style="width: 130px;">
                                    <select name="matches[{{ $match->id }}][actual_result]"
                                            class="form-select text-center">
                                        <option value=""></option>
                                        <option value="v" @selected($match->actual_result === 'v')>v</option>
                                        <option value="n" @selected($match->actual_result === 'n')>n</option>
                                        <option value="d" @selected($match->actual_result === 'd')>d</option>
                                    </select>
                                </td>

                                <td style="width: 110px;">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="matches[{{ $match->id }}][actual_tries]"
                                           value="{{ $match->actual_tries }}"
                                           class="form-control text-center">
                                </td>

                                <td style="width: 130px;">
                                    <select name="matches[{{ $match->id }}][actual_home_bonus]"
                                            class="form-select text-center">
                                        <option value=""></option>
                                        <option value="o" @selected($match->actual_home_bonus === 'o')>o</option>
                                        <option value="-" @selected($match->actual_home_bonus === '-')>-</option>
                                        <option value="d" @selected($match->actual_home_bonus === 'd')>d</option>
                                    </select>
                                </td>

                                <td style="width: 130px;">
                                    <select name="matches[{{ $match->id }}][actual_away_bonus]"
                                            class="form-select text-center">
                                        <option value=""></option>
                                        <option value="o" @selected($match->actual_away_bonus === 'o')>o</option>
                                        <option value="-" @selected($match->actual_away_bonus === '-')>-</option>
                                        <option value="d" @selected($match->actual_away_bonus === 'd')>d</option>
                                    </select>
                                </td>

                                <td class="text-center">
                                    @if($match->is_finished)
                                        <span class="badge text-bg-success rounded-pill">
                                            Terminé
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">
                                            Non saisi
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit"
                class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
            Enregistrer les résultats
        </button>
    </form>
@endif

@endsection
