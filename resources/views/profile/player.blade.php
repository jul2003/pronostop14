@extends('layouts.pronos')

@section('content')

@php
    $user = auth()->user();

    $playerColors = $playerColors ?? \App\Support\PlayerColorPalette::colors();

    $currentColor = strtoupper(old('color', $user->color ?? '#FFFF00'));

    $selectedColor = in_array($currentColor, $playerColors, true)
        ? $currentColor
        : '#FFFF00';
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="rugby-card overflow-hidden p-0">
            <div class="profile-hero">
                <div class="d-flex align-items-center gap-3">
                    <div id="profileAvatar"
                         class="profile-avatar"
                         style="background: {{ $selectedColor }}">
                        {{ strtoupper(substr($user->display_name, 0, 2)) }}
                    </div>

                    <div>
                        <div class="text-uppercase fw-bold text-warning small">
                            Mon profil
                        </div>

                        <h1 class="h2 fw-bold text-white mb-1">
                            {{ $user->display_name }}
                        </h1>

                        <div class="text-white-50">
                            Personnalise ton identité de pronostiqueur
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
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
                                   value="{{ old('nickname', $user->nickname) }}"
                                   class="form-control form-control-lg text-uppercase"
                                   required>

                            <div class="form-text">
                                Format obligatoire : 2 lettres suivies de 2 chiffres. Exemple : JA64.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Couleur
                            </label>

                            <div class="fw-bold text-dark mb-1">
                                Couleur joueur
                            </div>

                            <div class="small text-secondary mb-3">
                                Utilisée dans les classements et les pronos. Seules les couleurs de la palette sont autorisées.
                            </div>

                            <div class="player-color-palette">
                                @foreach($playerColors as $color)
                                    <label class="player-color-option"
                                           title="{{ $color }}">
                                        <input type="radio"
                                               name="color"
                                               value="{{ $color }}"
                                               class="player-color-input"
                                               required
                                               @checked($selectedColor === $color)>

                                        <span class="player-color-swatch"
                                              style="background-color: {{ $color }};">
                                            <span class="player-color-check">
                                                ✓
                                            </span>
                                        </span>

                                        <span class="visually-hidden">
                                            {{ $color }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            @error('color')
                                <div class="text-danger small mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h2 class="h5 fw-bold mb-1">
                            Coordonnées
                        </h2>

                        <p class="text-muted mb-0">
                            Ces adresses peuvent être utilisées pour la connexion et la réinitialisation du mot de passe.
                        </p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Email professionnel
                            </label>

                            <input type="email"
                                   name="email_pro"
                                   value="{{ old('email_pro', $user->email_pro) }}"
                                   class="form-control form-control-lg">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                Email personnel
                            </label>

                            <input type="email"
                                   name="email_perso"
                                   value="{{ old('email_perso', $user->email_perso) }}"
                                   class="form-control form-control-lg">
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        Au moins une des deux adresses email doit être renseignée.
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h2 class="h5 fw-bold mb-1">
                            Mot de passe
                        </h2>

                        <p class="text-muted mb-0">
                            Laisse ces champs vides si tu ne souhaites pas modifier ton mot de passe.
                        </p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Mot de passe actuel
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control form-control-lg">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Nouveau mot de passe
                            </label>

                            <input type="password"
                                   name="password"
                                   class="form-control form-control-lg">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                Confirmation
                            </label>

                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control form-control-lg">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit"
                                class="btn btn-warning rounded-pill fw-bold px-4">
                            Enregistrer mon profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .player-color-palette {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(42px, 1fr));
        gap: 0.75rem;
        max-width: 360px;
    }

    .player-color-option {
        position: relative;
        display: block;
        cursor: pointer;
    }

    .player-color-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .player-color-swatch {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 999px;
        border: 2px solid rgba(6, 20, 47, 0.2);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .player-color-check {
        display: none;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #06142f;
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 22px;
        text-align: center;
    }

    .player-color-option:hover .player-color-swatch {
        transform: translateY(-1px);
        box-shadow: 0 0.4rem 1rem rgba(6, 20, 47, 0.18);
    }

    .player-color-input:checked + .player-color-swatch {
        border-color: #06142f;
        box-shadow: 0 0 0 3px rgba(6, 20, 47, 0.18);
        transform: scale(1.05);
    }

    .player-color-input:checked + .player-color-swatch .player-color-check {
        display: inline-block;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const avatar = document.getElementById('profileAvatar');

        document.querySelectorAll('.player-color-input').forEach(function (input) {
            input.addEventListener('change', function () {
                if (! avatar) {
                    return;
                }

                avatar.style.background = input.value;
            });
        });
    });
</script>

@endsection
