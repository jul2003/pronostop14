@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.index'),
    'label' => 'Retour aux saisons',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Modifier la saison — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Tu peux uniquement la déverrouiller depuis cette page.
        @else
            Modifie les paramètres principaux de la saison.
        @endif
    </p>
</div>

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

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les clubs, joueurs, journées, barèmes, questions avant-saison et résultats ne peuvent plus être modifiés.
            Déverrouille la saison uniquement si tu dois corriger une donnée.
        </div>
    </div>

    <div class="rugby-card p-4">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Nom
                </label>

                <input type="text"
                       value="{{ $season->name }}"
                       class="form-control"
                       disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Slug
                </label>

                <input type="text"
                       value="{{ $season->slug }}"
                       class="form-control"
                       disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Statut
                </label>

                <input type="text"
                       value="{{ $season->is_active ? 'Active' : 'Inactive' }}"
                       class="form-control"
                       disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Clubs TOP 14
                </label>

                <input type="number"
                       value="{{ $season->top14_clubs_count }}"
                       class="form-control"
                       disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    Clubs PRO D2
                </label>

                <input type="number"
                       value="{{ $season->prod2_clubs_count }}"
                       class="form-control"
                       disabled>
            </div>
        </div>

        <form method="POST"
              id="unlock-season-form"
              action="{{ route('admin.seasons.update', $season) }}">
            @csrf
            @method('PUT')

            <input type="hidden"
                   name="unlock_season"
                   value="1">

            <button type="button"
                    class="btn btn-outline-danger rounded-pill fw-bold px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#unlockSeasonModal">
                Déverrouiller la saison
            </button>
        </form>
    </div>

    <div class="modal fade"
         id="unlockSeasonModal"
         tabindex="-1"
         aria-labelledby="unlockSeasonModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"
                        id="unlockSeasonModalLabel">
                        Déverrouiller la saison ?
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-3">
                        Tu vas déverrouiller la saison <strong>{{ $season->name }}</strong>.
                    </p>

                    <div class="alert alert-warning mb-0">
                        Une fois déverrouillée, les paramètres de la saison redeviendront modifiables :
                        clubs, joueurs, journées, barèmes, questions avant-saison et résultats.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-pill fw-bold"
                            data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <button type="submit"
                            form="unlock-season-form"
                            class="btn btn-danger rounded-pill fw-bold">
                        Confirmer le déverrouillage
                    </button>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="rugby-card p-4">
        <form method="POST" action="{{ route('admin.seasons.update', $season) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-bold">
                    Nom
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $season->name) }}"
                       class="form-control"
                       required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Nombre de clubs TOP 14
                    </label>

                    <input type="number"
                           name="top14_clubs_count"
                           value="{{ old('top14_clubs_count', $season->top14_clubs_count) }}"
                           class="form-control"
                           min="2"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Nombre de clubs PRO D2
                    </label>

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
                        class="btn btn-danger rounded-pill"
                        onclick="return confirm('Confirmer la suppression définitive de cette saison ?');">
                    Supprimer définitivement la saison
                </button>
            </form>
        </div>
    </div>
@endif

@endsection
