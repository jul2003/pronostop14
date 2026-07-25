@extends('layouts.pronos')

@section('content')

@php
    $user = auth()->user();

    $playerColors = collect($playerColors ?? \App\Support\PlayerColorPalette::colors())
        ->map(fn ($color) => strtoupper((string) $color))
        ->values()
        ->all();

    $usedPlayerColors = $usedPlayerColors ?? [];

    $currentColor = strtoupper((string) old('color', $user->color ?? ''));

    $firstAvailableColor = collect($playerColors)
        ->first(fn ($color) => ! array_key_exists(strtoupper($color), $usedPlayerColors));

    $selectedColor = in_array($currentColor, $playerColors, true)
        && ! array_key_exists($currentColor, $usedPlayerColors)
            ? $currentColor
            : ($firstAvailableColor ?? ($playerColors[0] ?? '#FFFF00'));

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

    $selectedTextColor = $contrastColor($selectedColor);
    $selectedTextShadow = $selectedTextColor === '#FFFFFF'
        ? '0 1px 2px rgba(0, 0, 0, 0.45)'
        : 'none';

    $avatarInitials = strtoupper(substr($user->nickname ?: $user->name, 0, 2));
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="rugby-card overflow-hidden p-0">
            <div class="profile-hero">
                <div class="d-flex align-items-center gap-3">
                    <div id="profileAvatar"
                         class="profile-avatar"
                         style="background: {{ $selectedColor }}; color: {{ $selectedTextColor }}; text-shadow: {{ $selectedTextShadow }};">
                        {{ $avatarInitials }}
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
                                Les couleurs déjà prises sont désactivées et affichent le pseudo du joueur concerné.
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
                            <label for="current_password" class="form-label fw-bold">
                                Mot de passe actuel
                            </label>

                            <div class="input-group">
                                <input id="current_password"
                                       type="password"
                                       name="current_password"
                                       class="form-control form-control-lg"
                                       autocomplete="current-password">

                                <button type="button"
                                        class="btn btn-outline-secondary password-toggle-button"
                                        data-password-toggle="current_password"
                                        aria-label="Afficher le mot de passe actuel">
                                    Afficher
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="password" class="form-label fw-bold">
                                Nouveau mot de passe
                            </label>

                            <div class="input-group">
                                <input id="password"
                                       type="password"
                                       name="password"
                                       class="form-control form-control-lg"
                                       autocomplete="new-password">

                                <button type="button"
                                        class="btn btn-outline-secondary password-toggle-button"
                                        data-password-toggle="password"
                                        aria-label="Afficher le nouveau mot de passe">
                                    Afficher
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="password_confirmation" class="form-label fw-bold">
                                Confirmation
                            </label>

                            <div class="input-group">
                                <input id="password_confirmation"
                                       type="password"
                                       name="password_confirmation"
                                       class="form-control form-control-lg"
                                       autocomplete="new-password">

                                <button type="button"
                                        class="btn btn-outline-secondary password-toggle-button"
                                        data-password-toggle="password_confirmation"
                                        aria-label="Afficher la confirmation">
                                    Afficher
                                </button>
                            </div>
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

@endsection

@push('styles')
<style>
    .profile-avatar {
        width: 72px;
        height: 72px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1.45rem;
        letter-spacing: 0.03em;
        border: 3px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 0.6rem 1.4rem rgba(0, 0, 0, 0.25);
    }

    .player-color-palette {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(48px, 1fr));
        gap: 0.75rem;
        max-width: 420px;
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
    function readableTextColor(hexColor) {
        const cleanHex = String(hexColor || '').replace('#', '');

        if (cleanHex.length !== 6) {
            return '#06142F';
        }

        const red = parseInt(cleanHex.substring(0, 2), 16);
        const green = parseInt(cleanHex.substring(2, 4), 16);
        const blue = parseInt(cleanHex.substring(4, 6), 16);
        const brightness = ((red * 299) + (green * 587) + (blue * 114)) / 1000;

        return brightness > 145 ? '#06142F' : '#FFFFFF';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const avatar = document.getElementById('profileAvatar');

        document.querySelectorAll('.player-color-input:not(:disabled)').forEach(function (input) {
            input.addEventListener('change', function () {
                if (!avatar) {
                    return;
                }

                const textColor = readableTextColor(input.value);

                avatar.style.background = input.value;
                avatar.style.color = textColor;
                avatar.style.textShadow = textColor === '#FFFFFF'
                    ? '0 1px 2px rgba(0, 0, 0, 0.45)'
                    : 'none';
            });
        });

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
