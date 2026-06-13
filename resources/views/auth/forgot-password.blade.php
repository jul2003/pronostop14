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
                        Mot de passe oublié
                    </h1>

                    <div class="text-white-50">
                        Saisis ton pseudo, ton email professionnel ou ton email personnel.
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                @if(session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Pseudo ou email
                        </label>

                        <input id="login"
                               type="text"
                               name="login"
                               value="{{ old('login') }}"
                               required
                               autofocus
                               autocomplete="username"
                               class="form-control form-control-lg">

                        <div class="form-text">
                            Exemple : JA64, email professionnel ou email personnel.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <a href="{{ route('login') }}"
                           class="text-decoration-none fw-bold">
                            ← Retour à la connexion
                        </a>

                        <button type="submit"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Envoyer le lien
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
