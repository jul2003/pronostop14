@extends('layouts.pronos')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">

        <div class="rugby-card overflow-hidden p-0">
            <div class="profile-hero">
                <div>
                    <div class="text-uppercase fw-bold text-warning small">
                        Pronos Top 14
                    </div>

                    <h1 class="h2 fw-bold text-white mb-1">
                        Nouveau mot de passe
                    </h1>

                    <div class="text-white-50">
                        Choisis un nouveau mot de passe pour ton compte.
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <input type="hidden"
                           name="email"
                           value="{{ old('email', $request->email) }}">

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Nouveau mot de passe
                        </label>

                        <input type="password"
                               name="password"
                               required
                               autocomplete="new-password"
                               class="form-control form-control-lg">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Confirmation
                        </label>

                        <input type="password"
                               name="password_confirmation"
                               required
                               autocomplete="new-password"
                               class="form-control form-control-lg">
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <a href="{{ route('login') }}"
                           class="text-decoration-none fw-bold">
                            ← Retour à la connexion
                        </a>

                        <button type="submit"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Réinitialiser
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
