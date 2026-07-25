@extends('layouts.pronos')

@section('content')

@php
    $playerColors = $playerColors ?? \App\Support\PlayerColorPalette::colors();
    $usedPlayerColors = $usedPlayerColors ?? [];

    $currentColor = strtoupper((string) old('color', $editedUser->color ?? ''));

    $firstAvailableColor = collect($playerColors)
        ->first(fn ($color) => ! array_key_exists(strtoupper($color), $usedPlayerColors));

    $selectedColor = in_array($currentColor, $playerColors, true)
        ? $currentColor
        : ($firstAvailableColor ?? ($playerColors[0] ?? '#FFFF00'));

    $isSelf = auth()->id() === $editedUser->id;
    $isProtected = $editedUser->isSuperAdmin() || $isSelf;
    $isActive = (bool) $editedUser->is_active;

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

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Modifier {{ $editedUser->display_name }}
        </h2>

        <p class="text-muted mb-0">
            Modifie les informations du joueur, son rôle et son statut.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @if($editedUser->isSuperAdmin())
            <span class="badge rounded-pill text-bg-warning px-3 py-2">
                Super Admin
            </span>
        @elseif($editedUser->role === 'admin')
            <span class="badge rounded-pill text-bg-primary px-3 py-2">
                Admin
            </span>
        @else
            <span class="badge rounded-pill text-bg-success px-3 py-2">
                Joueur
            </span>
        @endif

        @if($isActive)
            <span class="badge rounded-pill text-bg-success px-3 py-2">
                Actif
            </span>
        @else
            <span class="badge rounded-pill text-bg-secondary px-3 py-2">
                Désactivé
            </span>
        @endif
    </div>
</div>

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

<div class="row g-4">
    <div class="col-xl-8">
        <div class="rugby-card p-4">
            <form method="POST" action="{{ route('admin.users.update', $editedUser) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Nom
                        </label>

                        <input name="name"
                               value="{{ old('name', $editedUser->name) }}"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Pseudo
                        </label>

                        <input name="nickname"
                               value="{{ old('nickname', $editedUser->nickname) }}"
                               maxlength="4"
                               pattern="[A-Za-z]{2}[0-9]{2}"
                               class="form-control text-uppercase"
                               required>

                        <div class="form-text">
                            Format : 2 lettres + 2 chiffres, exemple JA64.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Email professionnel
                        </label>

                        <input name="email_pro"
                               type="email"
                               value="{{ old('email_pro', $editedUser->email_pro) }}"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            Email personnel
                        </label>

                        <input name="email_perso"
                               type="email"
                               value="{{ old('email_perso', $editedUser->email_perso) }}"
                               class="form-control">
                    </div>
                </div>

                <div class="form-text mt-2 mb-4">
                    Au moins une des deux adresses est obligatoire.
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
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Rôle
                    </label>

                    @if($editedUser->isSuperAdmin())
                        <select class="form-select" disabled>
                            <option>Super Admin</option>
                        </select>

                        <div class="form-text">
                            Le super admin ne peut pas être modifié.
                        </div>
                    @elseif($isSelf)
                        <select class="form-select" disabled>
                            <option>
                                {{ $editedUser->role === 'admin' ? 'Admin' : 'Joueur' }}
                            </option>
                        </select>

                        <div class="form-text">
                            Tu ne peux pas modifier ton propre rôle.
                        </div>
                    @else
                        <select name="role" class="form-select" required>
                            <option value="player" @selected(old('role', $editedUser->role) === 'player')>
                                Joueur
                            </option>

                            <option value="admin" @selected(old('role', $editedUser->role) === 'admin')>
                                Admin
                            </option>
                        </select>

                        <div class="form-text">
                            Utilise ce champ pour promouvoir ou rétrograder l’utilisateur.
                        </div>
                    @endif
                </div>

                <button class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer les modifications
                </button>
            </form>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="rugby-card p-4 mb-4">
            <h3 class="h5 fw-bold mb-2">
                Statut du compte
            </h3>

            <p class="text-muted">
                Désactiver empêche la connexion, mais conserve les pronos, scores et historiques.
            </p>

            @if($editedUser->isSuperAdmin())
                <div class="alert alert-warning mb-0">
                    Le super admin ne peut pas être désactivé.
                </div>
            @elseif($isSelf)
                <div class="alert alert-warning mb-0">
                    Tu ne peux pas désactiver ton propre compte.
                </div>
            @elseif($isActive)
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill fw-bold px-4"
                        data-inline-confirm-show="deactivate-confirmation">
                    Désactiver le compte
                </button>

                <div id="deactivate-confirmation" class="inline-confirmation d-none mt-3">
                    <div class="fw-bold mb-1">
                        Confirmer la désactivation ?
                    </div>

                    <p class="small text-muted mb-3">
                        {{ $editedUser->display_name }} ne pourra plus se connecter. Ses pronos, scores et historiques seront conservés.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST"
                              action="{{ route('admin.users.deactivate', $editedUser) }}">
                            @csrf

                            <button class="btn btn-secondary rounded-pill fw-bold px-4">
                                Oui, désactiver
                            </button>
                        </form>

                        <button type="button"
                                class="btn btn-outline-dark rounded-pill fw-bold px-4"
                                data-inline-confirm-hide="deactivate-confirmation">
                            Annuler
                        </button>
                    </div>
                </div>
            @else
                <form method="POST"
                      action="{{ route('admin.users.reactivate', $editedUser) }}">
                    @csrf

                    <button class="btn btn-success rounded-pill fw-bold px-4">
                        Réactiver le compte
                    </button>
                </form>
            @endif
        </div>

        @if(! $editedUser->isSuperAdmin())
            <div class="rugby-card p-4 mb-4">
                <h3 class="h5 fw-bold mb-2">
                    Reprise historique
                </h3>

                <p class="text-muted">
                    Le super admin peut saisir les pronos à la place du joueur, même si son compte est désactivé.
                </p>

                <form method="POST"
                      action="{{ route('admin.users.impersonate', $editedUser) }}">
                    @csrf

                    <button class="btn btn-outline-dark rounded-pill fw-bold px-4">
                        Saisir ses pronos
                    </button>
                </form>
            </div>
        @endif

        <div class="rugby-card p-4 border border-danger-subtle">
            <h3 class="h5 fw-bold text-danger mb-2">
                Suppression définitive
            </h3>

            <p class="text-muted">
                À utiliser uniquement en cas d’erreur de création. Un joueur avec historique doit être désactivé.
            </p>

            @if($editedUser->isSuperAdmin())
                <div class="alert alert-warning mb-0">
                    Le super admin ne peut pas être supprimé.
                </div>
            @elseif($isSelf)
                <div class="alert alert-warning mb-0">
                    Tu ne peux pas supprimer ton propre compte.
                </div>
            @elseif(! $canBeDeleted)
                <div class="alert alert-info mb-0">
                    Suppression désactivée car cet utilisateur possède déjà :
                    <ul class="mb-0 mt-2">
                        @foreach($deletionBlockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <button type="button"
                        class="btn btn-outline-danger rounded-pill fw-bold px-4"
                        data-inline-confirm-show="delete-confirmation">
                    Supprimer définitivement
                </button>

                <div id="delete-confirmation" class="inline-confirmation inline-confirmation-danger d-none mt-3">
                    <div class="fw-bold mb-1">
                        Confirmer la suppression définitive ?
                    </div>

                    <p class="small text-muted mb-3">
                        Cette action supprimera {{ $editedUser->display_name }} définitivement. Elle est irréversible.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST"
                              action="{{ route('admin.users.destroy', $editedUser) }}">
                            @csrf
                            @method('DELETE')

                            <button class="btn btn-danger rounded-pill fw-bold px-4">
                                Oui, supprimer
                            </button>
                        </form>

                        <button type="button"
                                class="btn btn-outline-dark rounded-pill fw-bold px-4"
                                data-inline-confirm-hide="delete-confirmation">
                            Annuler
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
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

    .inline-confirmation {
        border: 1px solid rgba(108, 117, 125, 0.35);
        background: rgba(108, 117, 125, 0.08);
        border-radius: 1rem;
        padding: 1rem;
    }

    .inline-confirmation-danger {
        border-color: rgba(220, 53, 69, 0.35);
        background: rgba(220, 53, 69, 0.08);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-inline-confirm-show]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.inlineConfirmShow);

                if (!target) {
                    return;
                }

                target.classList.remove('d-none');
                button.classList.add('d-none');
            });
        });

        document.querySelectorAll('[data-inline-confirm-hide]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.inlineConfirmHide);

                if (!target) {
                    return;
                }

                target.classList.add('d-none');

                const trigger = document.querySelector('[data-inline-confirm-show="' + button.dataset.inlineConfirmHide + '"]');

                if (trigger) {
                    trigger.classList.remove('d-none');
                }
            });
        });
    });
</script>
@endpush
