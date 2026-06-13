@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('pronos.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux pronos
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Résultats
    </div>

    <h2 class="fw-bold mb-1">
        {{ $journee->name }} — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Résultats, pronostics et points par joueur.
    </p>
</div>

<div class="rugby-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th rowspan="2">Match</th>
                    <th rowspan="2" class="text-center">Résultat</th>

                    @foreach($players as $player)
                        <th colspan="2" class="text-center">
                            {{ $player->nickname ?? $player->name }}
                        </th>
                    @endforeach
                </tr>

                <tr>
                    @foreach($players as $player)
                        <th class="text-center">Prono</th>
                        <th class="text-center">Pts</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($matches as $match)
                    <tr>
                        <td class="fw-bold">
                            {{ $match->homeClub->name }}
                            <span class="text-muted">—</span>
                            {{ $match->awayClub->name }}
                        </td>

                        <td class="text-center">
                            {{ $match->actual_result ?? '-' }}
                            /
                            {{ $match->actual_tries ?? '-' }}
                            /
                            {{ $match->actual_home_bonus ?: '-' }}
                            /
                            {{ $match->actual_away_bonus ?: '-' }}
                        </td>

                        @foreach($players as $player)
                            @php
                                $prono = $match->pronos->firstWhere('user_id', $player->id);
                            @endphp

                            <td class="text-center">
                                @if($prono)
                                    {{ $prono->predicted_result }}
                                    /
                                    {{ $prono->predicted_tries }}
                                    /
                                    {{ $prono->predicted_home_bonus ?: '-' }}
                                    /
                                    {{ $prono->predicted_away_bonus ?: '-' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td class="text-center fw-bold">
                                {{ $prono?->points ?? '-' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <tr class="table-light fw-bold">
                    <td colspan="2" class="text-end">
                        Total
                    </td>

                    @foreach($players as $player)
                        @php
                            $total = $matches->sum(function ($match) use ($player) {
                                return $match->pronos
                                    ->firstWhere('user_id', $player->id)
                                    ?->points ?? 0;
                            });
                        @endphp

                        <td></td>
                        <td class="text-center">
                            {{ $total }}
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
