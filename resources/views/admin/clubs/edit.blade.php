@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.clubs.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux équipes
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Modifier une équipe
    </h2>
</div>

<div class="rugby-card p-4">
    <form method="POST" action="{{ route('admin.clubs.update', $club) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label fw-bold">Nom</label>
            <input type="text"
                   name="name"
                   value="{{ old('name', $club->name) }}"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Nom court</label>
            <input type="text"
                   name="short_name"
                   value="{{ old('short_name', $club->short_name) }}"
                   class="form-control"
                   required>
        </div>

        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Enregistrer
        </button>
    </form>
</div>

@endsection
