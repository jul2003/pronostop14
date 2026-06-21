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

        <a href="{{ route('rankings.journee', [$season, $journee]) }}"
           class="btn btn-outline-primary rounded-pill fw-bold">
            Classement journée
        </a>
    </div>
</div>

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
                            @php
                                $prono = $match->pronos->first();
                            @endphp

                            <tr>
                                <td class="match-cell">
                                    <div class="match-line">

                                        <div class="match-home">
                                            <img src="{{ $match->homeClub->logo_url }}"
                                                alt="{{ $match->homeClub->name }}"
                                                class="club-logo-small">

                                            <span>
                                                {{ $match->homeClub->name }}
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
                                                {{ $match->awayClub->name }}
                                            </span>
                                        </div>

                                    </div>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach(['v' => 'V', 'n' => 'N', 'd' => 'D'] as $value => $label)
                                            <input type="radio"
                                                   id="result_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_result]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
                                                   @checked($prono?->predicted_result === $value)
                                                   @disabled($isLocked)
                                                   required>

                                            <label for="result_{{ $match->id }}_{{ $value }}"
                                                   class="prono-choice-label">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>

                                <td class="text-center">
                                    <input type="text"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           name="pronos[{{ $match->id }}][predicted_tries]"
                                           value="{{ $prono?->predicted_tries }}"
                                           class="form-control form-control-sm prono-tries-input mx-auto"
                                           required
                                           @disabled($isLocked)>
                                </td>

                                <td class="text-center">
                                    <div class="prono-choice-group">
                                        @foreach(['o' => 'O', '-' => '-', 'd' => 'D'] as $value => $label)
                                            <input type="radio"
                                                   id="home_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_home_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
                                                   @checked($prono?->predicted_home_bonus === $value)
                                                   @disabled($isLocked)>

                                            <label for="home_bonus_{{ $match->id }}_{{ $value }}"
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
                                                   id="away_bonus_{{ $match->id }}_{{ $value }}"
                                                   name="pronos[{{ $match->id }}][predicted_away_bonus]"
                                                   value="{{ $value }}"
                                                   class="prono-choice-input"
                                                   @checked($prono?->predicted_away_bonus === $value)
                                                   @disabled($isLocked)>

                                            <label for="away_bonus_{{ $match->id }}_{{ $value }}"
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
