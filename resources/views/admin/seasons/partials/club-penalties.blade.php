@php
    $top14Clubs = $season
        ->clubs()
        ->wherePivot('competition', 'top14')
        ->orderBy('name')
        ->get();

    $top14ClubIds = $top14Clubs
        ->pluck('id');

    $penalties = $season
        ->clubPenalties()
        ->with('club')
        ->whereIn(
            'club_id',
            $top14ClubIds
        )
        ->orderBy('effective_from_journee')
        ->orderBy('id')
        ->get();

    $penaltyTotals = $penalties
        ->groupBy('club_id')
        ->map(
            fn ($clubPenalties) =>
                $clubPenalties->sum(
                    'points_deduction'
                )
        );
@endphp

<div class="rugby-card p-4 mt-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase text-danger fw-bold small">
                Classement TOP 14
            </div>

            <h3 class="h5 fw-bold mb-1">
                Pénalités de points
            </h3>

            <p class="text-muted mb-0">
                Les pénalités sont propres à cette saison
                et sont déduites du classement TOP 14
                à partir de la journée indiquée.
            </p>
        </div>

        @if($penalties->isNotEmpty())
            <span class="badge rounded-pill text-bg-danger">
                {{ $penalties->count() }}
                pénalité(s)
            </span>
        @endif
    </div>

    <div class="alert alert-light border">
        <div class="small">
            L’absence de pénalité équivaut à
            <strong>0 point retiré</strong>.

            Une pénalité de
            <strong>4 points</strong>
            est enregistrée comme
            <strong>4</strong>
            puis affichée et calculée comme
            <strong>-4 points</strong>.
        </div>
    </div>

    @if($top14Clubs->isEmpty())
        <div class="alert alert-warning mb-0">
            Aucun club TOP 14 n’est actuellement
            sélectionné pour cette saison.
        </div>
    @else

        @unless($season->is_locked)
            <div class="border rounded-4 p-3 p-md-4 bg-light mb-4">
                <h4 class="h6 fw-bold mb-3">
                    Ajouter une pénalité
                </h4>

                <form method="POST"
                      action="{{ route('admin.seasons.club-penalties.store', $season) }}"
                      autocomplete="off">
                    @csrf

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="penalty_club_id"
                                   class="form-label fw-bold">
                                Club TOP 14
                            </label>

                            <select name="club_id"
                                    id="penalty_club_id"
                                    class="form-select"
                                    required>
                                <option value="">
                                    Choisir...
                                </option>

                                @foreach($top14Clubs as $club)
                                    <option value="{{ $club->id }}"
                                            @selected(
                                                (string) old('club_id')
                                                === (string) $club->id
                                            )>
                                        {{ $club->name }}

                                        @if(
                                            ($penaltyTotals[$club->id] ?? 0)
                                            > 0
                                        )
                                            — déjà
                                            -{{ $penaltyTotals[$club->id] }}
                                            pts
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="penalty_points"
                                   class="form-label fw-bold">
                                Points retirés
                            </label>

                            <input type="text"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   name="points_deduction"
                                   id="penalty_points"
                                   value="{{ old('points_deduction', 1) }}"
                                   class="form-control text-center penalty-value-input"
                                   autocomplete="off"
                                   autocorrect="off"
                                   autocapitalize="off"
                                   spellcheck="false"
                                   required>
                        </div>

                        <div class="col-md-2">
                            <label for="penalty_journee"
                                   class="form-label fw-bold">
                                À partir de
                            </label>

                            <select name="effective_from_journee"
                                    id="penalty_journee"
                                    class="form-select"
                                    required>
                                @for($journeeNumber = 1; $journeeNumber <= 26; $journeeNumber++)
                                    <option value="{{ $journeeNumber }}"
                                            @selected(
                                                (string) old(
                                                    'effective_from_journee',
                                                    1
                                                )
                                                === (string) $journeeNumber
                                            )>
                                        J{{ $journeeNumber }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="penalty_reason"
                                   class="form-label fw-bold">
                                Motif
                            </label>

                            <input type="text"
                                   name="reason"
                                   id="penalty_reason"
                                   value="{{ old('reason') }}"
                                   maxlength="255"
                                   class="form-control"
                                   placeholder="Optionnel"
                                   autocomplete="off">
                        </div>

                        <div class="col-12">
                            <button type="submit"
                                    class="btn btn-danger rounded-pill fw-bold px-4">
                                Ajouter la pénalité
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endunless

        @if($penalties->isEmpty())
            <div class="alert alert-success mb-0">
                Aucun club TOP 14 n’a de pénalité
                pour cette saison.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                Club
                            </th>

                            <th class="text-center">
                                Points retirés
                            </th>

                            <th class="text-center">
                                À partir de
                            </th>

                            <th>
                                Motif
                            </th>

                            <th class="text-center">
                                Total club
                            </th>

                            <th class="text-end">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($penalties as $penalty)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $penalty->club->logo_url }}"
                                             alt="{{ $penalty->club->name }}"
                                             class="club-logo-small">

                                        <span class="fw-bold">
                                            {{ $penalty->club->name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($season->is_locked)
                                        <span class="text-danger fw-bold">
                                            -{{ $penalty->points_deduction }}
                                        </span>
                                    @else
                                        <div class="input-group input-group-sm penalty-points-input mx-auto">
                                            <span class="input-group-text">
                                                -
                                            </span>

                                            <input type="text"
                                                   inputmode="numeric"
                                                   pattern="[0-9]*"
                                                   name="points_deduction"
                                                   value="{{ $penalty->points_deduction }}"
                                                   class="form-control text-center fw-bold penalty-value-input"
                                                   form="penalty-update-{{ $penalty->id }}"
                                                   autocomplete="off"
                                                   autocorrect="off"
                                                   autocapitalize="off"
                                                   spellcheck="false"
                                                   required>
                                        </div>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($season->is_locked)
                                        J{{ $penalty->effective_from_journee }}
                                    @else
                                        <select name="effective_from_journee"
                                                class="form-select form-select-sm penalty-j-select mx-auto"
                                                form="penalty-update-{{ $penalty->id }}"
                                                required>
                                            @for($journeeNumber = 1; $journeeNumber <= 26; $journeeNumber++)
                                                <option value="{{ $journeeNumber }}"
                                                        @selected(
                                                            (int) $penalty->effective_from_journee
                                                            === $journeeNumber
                                                        )>
                                                    J{{ $journeeNumber }}
                                                </option>
                                            @endfor
                                        </select>
                                    @endif
                                </td>

                                <td>
                                    @if($season->is_locked)
                                        {{ $penalty->reason ?: '—' }}
                                    @else
                                        <input type="text"
                                               name="reason"
                                               value="{{ $penalty->reason }}"
                                               maxlength="255"
                                               class="form-control form-control-sm"
                                               placeholder="Optionnel"
                                               form="penalty-update-{{ $penalty->id }}"
                                               autocomplete="off">
                                    @endif
                                </td>

                                <td class="text-center">
                                    <span class="badge rounded-pill text-bg-danger">
                                        -{{ $penaltyTotals[$penalty->club_id] ?? 0 }}
                                        pts
                                    </span>
                                </td>

                                <td class="text-end">
                                    @if($season->is_locked)
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @else
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-primary rounded-pill fw-bold"
                                                    form="penalty-update-{{ $penalty->id }}">
                                                Enregistrer
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger rounded-pill fw-bold"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deletePenaltyModal{{ $penalty->id }}">
                                                Supprimer
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @unless($season->is_locked)
                @foreach($penalties as $penalty)
                    <form id="penalty-update-{{ $penalty->id }}"
                          method="POST"
                          action="{{ route(
                              'admin.seasons.club-penalties.update',
                              [$season, $penalty]
                          ) }}"
                          class="d-none"
                          autocomplete="off">
                        @csrf
                        @method('PUT')
                    </form>

                    <div class="modal fade"
                         id="deletePenaltyModal{{ $penalty->id }}"
                         tabindex="-1"
                         aria-labelledby="deletePenaltyModalLabel{{ $penalty->id }}"
                         aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header border-0 pb-0">
                                    <div>
                                        <div class="text-uppercase text-danger fw-bold small">
                                            Pénalité TOP 14
                                        </div>

                                        <h2 class="modal-title h5 fw-bold mb-0"
                                            id="deletePenaltyModalLabel{{ $penalty->id }}">
                                            Supprimer la pénalité ?
                                        </h2>
                                    </div>

                                    <button type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Fermer">
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <p>
                                        Tu vas supprimer la pénalité de
                                        <strong>
                                            {{ $penalty->points_deduction }}
                                            point(s)
                                        </strong>
                                        appliquée à
                                        <strong>
                                            {{ $penalty->club->name }}
                                        </strong>
                                        à partir de
                                        <strong>
                                            J{{ $penalty->effective_from_journee }}
                                        </strong>.
                                    </p>

                                    @if($penalty->reason)
                                        <div class="alert alert-light border mb-0">
                                            <strong>Motif :</strong>
                                            {{ $penalty->reason }}
                                        </div>
                                    @endif
                                </div>

                                <div class="modal-footer border-0 pt-0">
                                    <button type="button"
                                            class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                                            data-bs-dismiss="modal">
                                        Annuler
                                    </button>

                                    <form method="POST"
                                          action="{{ route(
                                              'admin.seasons.club-penalties.destroy',
                                              [$season, $penalty]
                                          ) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-danger rounded-pill fw-bold px-4">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endunless
        @endif
    @endif
</div>

@once
    @push('styles')
        <style>
            .penalty-value-input {
                font-variant-numeric: tabular-nums;
            }

            .penalty-points-input {
                width: 95px;
            }

            .penalty-j-select {
                width: 85px;
            }
        </style>
    @endpush
@endonce
