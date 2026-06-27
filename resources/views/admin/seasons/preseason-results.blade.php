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

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
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
                                               class="form-control">
                                    @else
                                        <select name="results[{{ $question->id }}][club_id]"
                                                class="form-select">
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

        <div class="d-flex justify-content-end mt-4">
            <button type="submit"
                    class="btn btn-warning rounded-pill fw-bold px-4">
                Enregistrer et recalculer
            </button>
        </div>
    </form>

@endif

@endsection
