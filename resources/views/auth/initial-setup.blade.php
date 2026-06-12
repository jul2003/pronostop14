@extends('layouts.pronos')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="rugby-card p-4">
            <h2 class="fw-bold mb-3">
                Initialisation du site
            </h2>

            <p class="text-muted">
                Crée le premier compte super admin.
            </p>

            <form method="POST" action="{{ route('initial-setup.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pseudo</label>
                    <input name="nickname" maxlength="4" pattern="[A-Za-z]{2}[0-9]{2}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Couleur</label>
                    <input name="color" type="color" value="#ffd200" class="form-control form-control-color">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input name="password" type="password" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirmation</label>
                    <input name="password_confirmation" type="password" class="form-control" required>
                </div>

                <button class="btn btn-warning rounded-pill fw-bold px-4">
                    Créer le super admin
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
