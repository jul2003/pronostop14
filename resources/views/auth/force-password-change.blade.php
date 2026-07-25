@extends('layouts.pronos')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="rugby-card p-4">
            <div class="mb-4">
                <div class="text-uppercase text-primary fw-bold small">
                    Sécurité
                </div>

                <h2 class="fw-bold mb-1">
                    Changement du mot de passe
                </h2>

                <p class="text-muted mb-0">
                    Ton compte utilise un mot de passe temporaire. Tu dois le changer avant de continuer.
                </p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.force.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">
                        Nouveau mot de passe
                    </label>

                    <div class="input-group">
                        <input id="password"
                               name="password"
                               type="password"
                               class="form-control"
                               autocomplete="new-password"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary password-toggle-button"
                                data-password-toggle="password"
                                aria-label="Afficher le mot de passe">
                            Afficher
                        </button>
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
                               autocomplete="new-password"
                               required>

                        <button type="button"
                                class="btn btn-outline-secondary password-toggle-button"
                                data-password-toggle="password_confirmation"
                                aria-label="Afficher la confirmation du mot de passe">
                            Afficher
                        </button>
                    </div>
                </div>

                <button class="btn btn-warning rounded-pill fw-bold px-4">
                    Enregistrer le nouveau mot de passe
                </button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
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
