@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.index'),
    'label' => 'Retour aux saisons',
])

<div class="mb-4">

    <h2 class="fw-bold mt-3 mb-1">
        Modifier la saison
    </h2>
</div>

<div class="rugby-card p-4">
    <form method="POST" action="{{ route('admin.seasons.update', $season) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label fw-bold">Nom</label>
            <input type="text"
                   name="name"
                   value="{{ old('name', $season->name) }}"
                   class="form-control"
                   required>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Nombre de clubs TOP 14</label>
                <input type="number"
                       name="top14_clubs_count"
                       value="{{ old('top14_clubs_count', $season->top14_clubs_count) }}"
                       class="form-control"
                       min="2"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Nombre de clubs PRO D2</label>
                <input type="number"
                       name="prod2_clubs_count"
                       value="{{ old('prod2_clubs_count', $season->prod2_clubs_count) }}"
                       class="form-control"
                       min="0"
                       required>
            </div>
        </div>

        <div class="form-check mt-4">
            <input class="form-check-input"
                   type="checkbox"
                   name="is_active"
                   value="1"
                   id="is_active"
                   @checked(old('is_active', $season->is_active))>

            <label class="form-check-label fw-bold" for="is_active">
                Saison active
            </label>
        </div>

        <button type="submit"
                class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
            Enregistrer
        </button>
    </form>
    <hr class="my-5">

    <div class="border border-danger rounded-4 p-4">

        <h4 class="text-danger fw-bold">
            Zone dangereuse
        </h4>

        <p class="text-muted mb-4">
            La suppression d'une saison supprimera également :
        </p>

        <ul class="text-muted">
            <li>les journées associées</li>
            <li>les matchs associés</li>
            <li>les participants de la saison</li>
            <li>les pronostics liés à ces matchs</li>
        </ul>

        <form method="POST"
            action="{{ route('admin.seasons.destroy', $season) }}">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-danger rounded-pill">
                Supprimer définitivement la saison
            </button>
        </form>

    </div>
</div>



@endsection
