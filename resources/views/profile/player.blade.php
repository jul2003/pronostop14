@extends('layouts.pronos')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="rugby-card overflow-hidden p-0">
            <div class="profile-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="profile-avatar"
                         style="background: {{ auth()->user()->color ?? '#ffd200' }}">
                        {{ strtoupper(substr(auth()->user()->display_name, 0, 2)) }}
                    </div>

                    <div>
                        <div class="text-uppercase fw-bold text-warning small">
                            Mon profil
                        </div>

                        <h1 class="h2 fw-bold text-white mb-1">
                            {{ auth()->user()->display_name }}
                        </h1>

                        <div class="text-white-50">
                            Personnalise ton identité de pronostiqueur
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                <form method="POST" action="{{ route('player-profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Pseudo
                            </label>

                            <input type="text"
                                name="nickname"
                                maxlength="4"
                                pattern="[A-Za-z]{2}[0-9]{2}"
                                value="{{ old('nickname', auth()->user()->nickname) }}"
                                class="form-control form-control-lg"
                                required>

                            <div class="form-text">
                                Format obligatoire : 2 lettres suivies de 2 chiffres. Exemple : JA64.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Couleur
                            </label>

                            <div class="d-flex align-items-center gap-3">
                                <input type="color"
                                       name="color"
                                       value="{{ old('color', auth()->user()->color ?? '#ffd200') }}"
                                       class="form-control form-control-color profile-color-input"
                                       title="Choisir ma couleur">

                                <div>
                                    <div class="fw-bold text-dark">
                                        Couleur joueur
                                    </div>
                                    <div class="small text-secondary">
                                        Utilisée dans les classements et les pronos.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="small text-secondary">
                            Email : <strong>{{ auth()->user()->email }}</strong>
                        </div>

                        <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer mon profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
