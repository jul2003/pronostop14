@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.users.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux utilisateurs
    </a>

    <h2 class="fw-bold mt-3 mb-1">
        Ajouter un utilisateur
    </h2>
</div>

<div class="rugby-card p-4">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-bold">Nom</label>
            <input name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Pseudo</label>
            <input name="nickname"
                   maxlength="4"
                   pattern="[A-Za-z]{2}[0-9]{2}"
                   class="form-control"
                   required>
            <div class="form-text">Format : 2 lettres + 2 chiffres, exemple JA64.</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Email</label>
            <input name="email" type="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Couleur</label>
            <input name="color" type="color" value="#ffd200" class="form-control form-control-color">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Rôle</label>
            <select name="role" class="form-select" required>
                <option value="player">Joueur</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Mot de passe</label>
            <input name="password" type="password" class="form-control" required>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Confirmation</label>
            <input name="password_confirmation" type="password" class="form-control" required>
        </div>

        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Créer l’utilisateur
        </button>
    </form>
</div>

@endsection
