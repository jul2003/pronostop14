@extends('layouts.pronos')

@section('content')

<div class="mb-4">

    <a href="{{ route('admin.seasons.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux saisons
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Ajouter une saison
    </h2>

    <p class="text-muted mb-0">
        Exemple : TOP 14 2025-2026
    </p>

</div>

<div class="rugby-card p-4">

    <form method="POST" action="{{ route('admin.seasons.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-bold">
                Nom
            </label>

            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   class="form-control"
                   placeholder="TOP 14 2025-2026"
                   required>
        </div>

        <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Nombre de clubs TOP 14
                    </label>

                    <input type="number"
                        name="top14_clubs_count"
                        value="{{ old('top14_clubs_count', 14) }}"
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
                        value="{{ old('prod2_clubs_count', 16) }}"
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
                   @checked(old('is_active'))>

            <label class="form-check-label fw-bold"
                   for="is_active">
                Définir comme saison active
            </label>

        </div>

        <button type="submit"
                class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
            Créer la saison
        </button>

    </form>

</div>

@endsection
