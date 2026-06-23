@extends('layouts.pronos')

@section('content')

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
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
            {{ $season->name }}
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.seasons.journees.matches', [$season, $journee]) }}"
           class="btn btn-outline-primary rounded-pill fw-bold">
            Matchs
        </a>
    </div>
</div>

@if($matches->isEmpty())

    <div class="alert alert-info">
        Aucun match disponible pour cette journée.
    </div>

@else

    <form method="POST"
          action="{{ route('admin.seasons.journees.results.store', [$season, $journee]) }}">
        @csrf

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 prono-table">
                    <thead class="table-light">
                        <tr>
                            <th>Match</th>
                            <th class="text-center">Résultat</th>
                            <th class="text-center">Essais</th>
                            <th class="text-center">Bonus dom.</th>
                            <th class="text-center">Bonus ext.</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matches as $match)
                            <tr>
                                <td class="match-cell">
                                    <div class="match-line">

                                        <div class="match-home">
                                            <img src="{{ $match->homeClub->logo_url }}"
                                                alt="{{ $match->homeClub->name }}"
                                                class="club-logo-small">

                                            <span>
                                                {{ $match->homeClub->short_name ?? $match->homeClub->name }}
                                            </span>
                                        </div>

                                        <div class="match-separator">
                                            -
                                        </div>

                                        <div class="match-away">
                                            <img src="{{ $match->awayClub->logo_url }}"
                                                alt="{{ $match->awayClub->name }}"
                                                class="club-logo-small">

                                            <span>
                                                {{ $match->awayClub->short_name ?? $match->awayClub->name }}
                                            </span>
                                        </div>

                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach($journee->resultOptionShortLabels() as $value => $label)
                                            <input type="radio"
                                                id="actual_result_{{ $match->id }}_{{ $value }}"
                                                name="matches[{{ $match->id }}][actual_result]"
                                                value="{{ $value }}"
                                                class="prono-choice-input"
                                                @checked($match->actual_result === $value)>

                                            <label for="actual_result_{{ $match->id }}_{{ $value }}"
                                                class="prono-choice-label"
                                                title="{{ $journee->resultOptionLabel($value) }}">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="matches[{{ $match->id }}][actual_tries]"
                                           value="{{ $match->actual_tries }}"
                                           class="form-control form-control-sm prono-tries-input mx-auto">
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach(['o' => 'O', '-' => '-', 'd' => 'D'] as $value => $label)
                                            <input type="radio"
                                                   id="actual_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="matches[{{ $match->id }}][actual_home_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
                                                   @checked($match->actual_home_bonus === $value)>

                                            <label for="actual_home_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach(['o' => 'O', '-' => '-', 'd' => 'D'] as $value => $label)
                                            <input type="radio"
                                                   id="actual_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="matches[{{ $match->id }}][actual_away_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
                                                   @checked($match->actual_away_bonus === $value)>

                                            <label for="actual_away_bonus_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit"
                    class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer les résultats
            </button>
        </div>
    </form>

@endif

<script>
    document.querySelectorAll('.prono-choice-input').forEach(input => {
        input.addEventListener('click', function () {
            if (this.dataset.wasChecked === 'true') {
                this.checked = false;
                this.dataset.wasChecked = 'false';
            } else {
                document
                    .querySelectorAll(`input[name="${this.name}"]`)
                    .forEach(radio => radio.dataset.wasChecked = 'false');

                this.dataset.wasChecked = 'true';
            }
        });
    });

    document.querySelectorAll('.prono-choice-input:checked').forEach(input => {
        input.dataset.wasChecked = 'true';
    });
</script>

@endsection
