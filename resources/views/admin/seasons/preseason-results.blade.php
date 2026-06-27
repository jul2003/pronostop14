@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.journees', $season),
    'label' => 'Retour aux journées',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Résultats avant-saison — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Saisis les réponses officielles des questions avant-saison. Les points sont recalculés à chaque enregistrement.
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-info">
        Cette saison est verrouillée. Les résultats avant-saison sont consultables uniquement.
        Pour les modifier, il faut d’abord déverrouiller la saison depuis sa page d’édition.
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($questions->isEmpty())

    <div class="alert alert-info">
        Aucune question avant-saison active pour cette saison.
    </div>

@else

    <form method="POST"
          id="preseason-results-form"
          action="{{ route('admin.seasons.preseason-results.update', $season) }}">
        @csrf
        @method('PUT')

        <div class="rugby-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">
                                Question
                            </th>
                            <th>
                                Résultat officiel
                            </th>
                            <th class="text-center" style="width: 140px;">
                                Statut
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($questions as $question)
                            @php
                                $clubs = match ($question->answer_type) {
                                    'top14_club' => $top14Clubs,
                                    'prod2_club' => $prod2Clubs,
                                    'season_club' => $seasonClubs,
                                    default => collect(),
                                };
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-bold">
                                        {{ $question->label }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ $question->points }} point(s)
                                    </div>
                                </td>

                                <td>
                                    @if($question->answer_type === 'free_text')
                                        <input type="text"
                                               name="results[{{ $question->id }}][text_answer]"
                                               value="{{ old("results.{$question->id}.text_answer", $question->result_text_answer) }}"
                                               class="form-control"
                                               @disabled($season->is_locked)>
                                    @else
                                        <select name="results[{{ $question->id }}][club_id]"
                                                class="form-select"
                                                @disabled($season->is_locked)>
                                            <option value="">
                                                Non renseigné
                                            </option>

                                            @foreach($clubs as $club)
                                                <option value="{{ $club->id }}"
                                                        @selected((string) old("results.{$question->id}.club_id", $question->result_club_id) === (string) $club->id)>
                                                    {{ $club->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($question->hasOfficialResult())
                                        <span class="badge bg-success">
                                            Saisi
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            En attente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @unless($season->is_locked)
            <div class="d-flex flex-column flex-lg-row justify-content-end gap-2 mt-4">
                <button type="submit"
                        name="lock_season"
                        value="0"
                        class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer et recalculer
                </button>

                <button type="button"
                        class="btn btn-outline-danger rounded-pill fw-bold px-4"
                        data-bs-toggle="modal"
                        data-bs-target="#lockSeasonModal">
                    Enregistrer, recalculer et verrouiller la saison
                </button>
            </div>

            <div class="modal fade"
                 id="lockSeasonModal"
                 tabindex="-1"
                 aria-labelledby="lockSeasonModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"
                                id="lockSeasonModalLabel">
                                Verrouiller la saison ?
                            </h5>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p class="mb-3">
                                Cette action va enregistrer les résultats avant-saison, recalculer les points, puis verrouiller la saison.
                            </p>

                            <div class="alert alert-warning mb-0">
                                Une fois verrouillée, la saison passera en consultation seule :
                                clubs, joueurs, paramètres, résultats et configuration ne seront plus modifiables tant que la saison n’est pas déverrouillée.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-outline-secondary rounded-pill fw-bold"
                                    data-bs-dismiss="modal">
                                Annuler
                            </button>

                            <button type="submit"
                                    name="lock_season"
                                    value="1"
                                    form="preseason-results-form"
                                    class="btn btn-danger rounded-pill fw-bold">
                                Confirmer et verrouiller
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endunless
    </form>

@endif

@endsection
