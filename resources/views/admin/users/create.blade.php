@extends('layouts.pronos')

@section('content')

@php
    $playerColors = $playerColors ?? \App\Support\PlayerColorPalette::colors();
    $usedPlayerColors = $usedPlayerColors ?? [];

    $firstAvailableColor = collect($playerColors)
        ->first(fn ($color) => ! array_key_exists(strtoupper($color), $usedPlayerColors));

    $selectedColor = strtoupper((string) old('color', $firstAvailableColor ?? ''));

    $contrastColor = function (string $hexColor) {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) !== 6) {
            return '#06142F';
        }

        $red = hexdec(substr($hexColor, 0, 2));
        $green = hexdec(substr($hexColor, 2, 2));
        $blue = hexdec(substr($hexColor, 4, 2));

        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 145 ? '#06142F' : '#FFFFFF';
    };
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
        Crée un utilisateur, choisis sa couleur et envoie ses accès par mail.
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
            Au moins une des deux adresses est obligatoire. Le mot de passe sera envoyé à la ou les adresses renseignées.
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">
                Couleur
            </label>

            <div class="form-text mb-3">
                Les couleurs déjà utilisées sont désactivées et affichent le pseudo du joueur concerné.
            </div>

            <div class="player-color-palette">
                @foreach($playerColors as $color)
                    @php
                        $normalizedColor = strtoupper($color);
                        $usedByNickname = $usedPlayerColors[$normalizedColor] ?? null;
                        $isUsed = filled($usedByNickname);
                        $textColor = $contrastColor($normalizedColor);
                        $textShadow = $textColor === '#FFFFFF'
                            ? '0 1px 2px rgba(0, 0, 0, 0.45)'
                            : 'none';
                    @endphp

                    <label class="player-color-option {{ $isUsed ? 'is-used' : '' }}"
                           title="{{ $isUsed ? 'Déjà prise par '.$usedByNickname : $normalizedColor }}">
                        <input type="radio"
                               name="color"
                               value="{{ $normalizedColor }}"
                               class="player-color-input"
                               required
                               @disabled($isUsed)
                               @checked(! $isUsed && $selectedColor === $normalizedColor)>

                        <span class="player-color-swatch"
                              style="background-color: {{ $normalizedColor }}; color: {{ $textColor }}; --swatch-text-shadow: {{ $textShadow }};">
                            @if($isUsed)
                                <span class="player-color-used-label">
                                    {{ $usedByNickname }}
                                </span>
                            @else
                                <span class="player-color-check">
                                    ✓
                                </span>
                            @endif
                        </span>

                        <span class="visually-hidden">
                            {{ $normalizedColor }}
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
            <label for="password" class="form-label fw-bold">
                Mot de passe
            </label>

            <div class="input-group">
                <input id="password"
                       name="password"
                       type="password"
                       class="form-control"
                       autocomplete="new-password">

                <button type="button"
                        class="btn btn-outline-secondary password-toggle-button"
                        data-password-toggle="password"
                        aria-label="Afficher le mot de passe">
                    Afficher
                </button>
            </div>

            <div class="form-text">
                Laisse vide pour générer automatiquement un mot de passe. Dans tous les cas, il sera envoyé par mail et devra être changé à la première connexion.
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-bold">
                Confirmation
            </label>

            <div class="input-group">
                <input id="password_confirmation"
                       name="password_confirmation"
                       type="password"
                       class="form-control"
                       autocomplete="new-password">

                <button type="button"
                        class="btn btn-outline-secondary password-toggle-button"
                        data-password-toggle="password_confirmation"
                        aria-label="Afficher la confirmation du mot de passe">
                    Afficher
                </button>
            </div>

            <div class="form-text">
                À remplir seulement si tu saisis toi-même un mot de passe.
            </div>
        </div>

        <button class="btn btn-warning rounded-pill fw-bold px-4">
            Créer l’utilisateur et envoyer le mail
        </button>
    </form>
</div>

@endsection

@push('styles')
<style>
    .player-color-palette {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(48px, 1fr));
        gap: 0.75rem;
        max-width: 620px;
    }

    .player-color-option {
        position: relative;
        display: block;
        cursor: pointer;
    }

    .player-color-option.is-used {
        cursor: not-allowed;
    }

    .player-color-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .player-color-swatch {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 999px;
        border: 2px solid rgba(6, 20, 47, 0.2);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
    }

    .player-color-check {
        display: none;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #06142f;
        font-size: 0.95rem;
        font-weight: 800;
        line-height: 24px;
        text-align: center;
    }

    .player-color-used-label {
        position: relative;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.24);
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        text-align: center;
        line-height: 1;
        overflow: hidden;
        color: inherit;
        text-shadow: var(--swatch-text-shadow);
    }

    .player-color-option:not(.is-used):hover .player-color-swatch {
        transform: translateY(-1px);
        box-shadow: 0 0.4rem 1rem rgba(6, 20, 47, 0.18);
    }

    .player-color-option.is-used .player-color-swatch {
        opacity: 0.66;
        filter: grayscale(0.2);
    }

    .player-color-option.is-used .player-color-swatch::after {
        content: "";
        position: absolute;
        z-index: 1;
        width: 54px;
        height: 2px;
        background: rgba(6, 20, 47, 0.72);
        transform: rotate(-35deg);
        border-radius: 999px;
        pointer-events: none;
    }

    .player-color-input:checked + .player-color-swatch {
        border-color: #06142f;
        box-shadow: 0 0 0 3px rgba(6, 20, 47, 0.18);
        transform: scale(1.05);
    }

    .player-color-input:checked + .player-color-swatch .player-color-check {
        display: inline-block;
    }

    .password-toggle-button {
        min-width: 92px;
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.passwordToggle);

                if (!input) {
                    return;
                }

                const shouldShow = input.type === 'password';

                input.type = shouldShow ? 'text' : 'password';
                button.textContent = shouldShow ? 'Masquer' : 'Afficher';
                button.setAttribute(
                    'aria-label',
                    shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
                );
            });
        });
    });
</script>
@endpush
