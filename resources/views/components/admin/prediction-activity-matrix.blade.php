@php
    $playerLabel = fn ($player) =>
        $player->nickname
        ?? $player->name;

    $safeColor = function (
        $value,
        $fallback = '#06142F'
    ) {
        $color = strtoupper(
            (string) $value
        );

        return preg_match(
            '/^#[0-9A-F]{6}$/',
            $color
        )
            ? $color
            : $fallback;
    };

    $journeeLabel = function ($journee) {
        return match ($journee->type) {
            'preseason' =>
                'Avant-saison',

            'regular' =>
                'J'.$journee->number,

            'prod2_final' =>
                'Finale PRO D2',

            'access_match' =>
                'Access Match',

            'top14_playoff' =>
                'Barrages TOP 14',

            'top14_semifinal' =>
                'Demi-finales TOP 14',

            'top14_final' =>
                'Finale TOP 14',

            default =>
                $journee->name
                    ?: $journee->type_label,
        };
    };
@endphp

<div class="rugby-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="text-uppercase text-primary fw-bold small">
                    Suivi
                </div>

                <h3 class="h5 fw-bold mb-1">
                    Enregistrement des pronostics
                </h3>

                <p class="text-muted mb-0">
                    Dernière date et heure d’enregistrement
                    pour chaque joueur et chaque journée.
                </p>
            </div>

            <span class="badge rounded-pill text-bg-dark">
                {{ $players->count() }}
                joueur(s)
            </span>
        </div>

        <div class="small text-muted mt-3">
            <strong>—</strong>
            signifie qu’aucun pronostic n’est actuellement
            enregistré pour ce joueur sur cette ligne.
        </div>
    </div>

    @if($players->isEmpty())
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Aucun joueur n’est inscrit à cette saison.
            </div>
        </div>
    @elseif($journees->isEmpty())
        <div class="p-4">
            <div class="alert alert-info mb-0">
                Aucune journée n’est encore générée
                pour cette saison.
            </div>
        </div>
    @else
        <div class="prediction-activity-wrapper">
            <table class="table table-hover align-middle mb-0 prediction-activity-table">
                <thead>
                    <tr>
                        <th class="prediction-activity-journee-head">
                            Journée
                        </th>

                        @foreach($players as $player)
                            <th class="prediction-activity-player-head"
                                style="--player-color: {{ $safeColor($player->color ?? null) }};">
                                {{ $playerLabel($player) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($journees as $journee)
                        <tr>
                            <td class="prediction-activity-journee-cell">
                                <div class="fw-bold">
                                    {{ $journeeLabel($journee) }}
                                </div>

                                @if(
                                    $journee->type !== 'preseason'
                                    && $journee->first_match_at
                                )
                                    <div class="small text-muted mt-1">
                                        {{ $journee->first_match_at->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>

                            @foreach($players as $player)
                                @php
                                    $lastSavedAt =
                                        $activity[
                                            $journee->id
                                        ][
                                            $player->id
                                        ] ?? null;
                                @endphp

                                <td class="prediction-activity-date-cell">
                                    @if($lastSavedAt)
                                        <div class="prediction-activity-date">
                                            {{ $lastSavedAt->format('d/m/Y') }}
                                        </div>

                                        <div class="prediction-activity-time">
                                            {{ $lastSavedAt->format('H:i') }}
                                        </div>
                                    @else
                                        <span class="prediction-activity-empty">
                                            —
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@once
    @push('styles')
        <style>
            .prediction-activity-wrapper {
                max-height: 72vh;
                overflow: auto;
            }

            .prediction-activity-table {
                border-collapse: separate;
                border-spacing: 0;
                min-width: max-content;
                font-size: 0.82rem;
            }

            .prediction-activity-table th,
            .prediction-activity-table td {
                padding: 0.55rem 0.7rem;
                vertical-align: middle;
            }

            .prediction-activity-table thead th {
                position: sticky;
                top: 0;
                z-index: 20;
                background: #f8f9fa;
                border-bottom: 2px solid rgba(
                    6,
                    20,
                    47,
                    0.18
                );
            }

            .prediction-activity-journee-head,
            .prediction-activity-journee-cell {
                position: sticky;
                left: 0;
                min-width: 180px;
                width: 180px;
                max-width: 180px;
                border-right: 2px solid rgba(
                    6,
                    20,
                    47,
                    0.18
                ) !important;
            }

            .prediction-activity-journee-head {
                z-index: 30 !important;
                background: #f8f9fa !important;
            }

            .prediction-activity-journee-cell {
                z-index: 10;
                background: #ffffff !important;
            }

            .prediction-activity-table tbody tr:hover
            .prediction-activity-journee-cell {
                background: #f8f9fa !important;
            }

            .prediction-activity-player-head {
                min-width: 130px;
                width: 130px;
                max-width: 130px;
                color: var(
                    --player-color,
                    #06142F
                ) !important;
                font-weight: 800;
                text-align: center;
                white-space: nowrap;
            }

            .prediction-activity-date-cell {
                min-width: 130px;
                width: 130px;
                max-width: 130px;
                text-align: center;
                white-space: nowrap;
            }

            .prediction-activity-date {
                font-weight: 800;
                color: #06142F;
            }

            .prediction-activity-time {
                margin-top: 0.1rem;
                color: #6c757d;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .prediction-activity-empty {
                color: #adb5bd;
                font-size: 1.05rem;
                font-weight: 700;
            }

            @media (max-width: 767.98px) {
                .prediction-activity-journee-head,
                .prediction-activity-journee-cell {
                    min-width: 135px;
                    width: 135px;
                    max-width: 135px;
                }

                .prediction-activity-player-head,
                .prediction-activity-date-cell {
                    min-width: 105px;
                    width: 105px;
                    max-width: 105px;
                }

                .prediction-activity-table {
                    font-size: 0.75rem;
                }
            }
        </style>
    @endpush
@endonce
