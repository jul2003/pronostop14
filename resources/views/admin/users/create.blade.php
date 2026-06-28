@extends('layouts.pronos')

@section('content')

@php
    $playerColors = $playerColors ?? \App\Support\PlayerColorPalette::colors();
    $selectedColor = strtoupper(old('color', '#FFFF00'));
@endphp

@include('admin.partials.back-link', [
    'href' => route('admin.users.index'),
    'label' => 'Retour aux utilisateurs',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Ajouter un utilisateur
    </h2>

    <p class="text-muted mb-0">
        Crée un utilisateur et choisis sa couleur dans la palette autorisée.
    </p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<div class="rugby-card p-4">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-bold">
                Nom
            </label>

            <input name="name"
                   value="{{ old('name') }}"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">
                Pseudo
            </label>

            <input name="nickname"
                   value="{{ old('nickname') }}"
                   maxlength="4"
                   pattern="[A-Za-z]{2}[0-9]{2}"
                   class="form-control text-uppercase"
                   required>

            <div class="form-text">
                Format : 2 lettres + 2 chiffres, exemple JA64.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">
                Email professionnel
            </label>

            <input name="email_pro"
                   type="email"
                   value="{{ old('email_pro') }}"
                   class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">
                Email personnel
            </label>

            <input name="email_perso"
                   type="email"
                   value="{{ old('email_perso') }}"
                   class="form-control">
        </div>

        <div class="form-text mb-3">
            Au moins une des deux adresses est obligatoire.
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">
                Couleur
            </label>

            <div class="form-text mb-3">
                Seules les 27 couleurs de la palette contrôlée sont autorisées.
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

        <div class="mb-3">
            <label class="form-label fw-bold">
                Rôle
            </label>

            <select name="role" class="form-select" required>
                <option value="player" @selected(old('role', 'player') === 'player')>
                    Joueur
                </option>

                <option value="admin" @selected(old('role') === 'admin')>
                    Admin
                </option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">
                Mot de passe
            </label>

            <input name="password"
                   type="password"
                   class="form-control"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">
                Confirmation
            </label>

            <input name="password_confirmation"
                   type="password"
                   class="form-control"
                   required>
        </div>

        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Créer l’utilisateur
        </button>
    </form>
</div>

<style>
    .player-color-palette {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(42px, 1fr));
        gap: 0.75rem;
        max-width: 520px;
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

@endsection
