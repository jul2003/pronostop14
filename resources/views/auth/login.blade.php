<x-guest-layout>
    <div class="login-page">
        <div class="login-card">

            <div class="text-center mb-4">
                <div class="text-uppercase fw-bold text-warning small">
                    Pronos Top 14
                </div>

                <h1 class="h2 fw-bold text-white mt-3 mb-2">
                    Connexion
                </h1>

                <p class="text-white-50 mb-0">
                    Accède à tes pronostics, classements et résultats.
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label text-white-50 fw-bold" for="login">
                        Email ou pseudo
                    </label>

                    <input id="login"
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="form-control login-control">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white-50 fw-bold" for="password">
                        Mot de passe
                    </label>

                    <div class="input-group">
                        <input id="password"
                               type="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               class="form-control login-control">

                        <button class="btn login-toggle"
                                type="button"
                                onclick="togglePassword('password', this)">
                            Afficher
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                    <label class="d-flex align-items-center gap-2 text-white-50 small mb-0">
                        <input type="checkbox"
                               name="remember"
                               class="form-check-input m-0">
                        Se souvenir de moi
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-warning small fw-bold text-decoration-none">
                            Mot de passe oublié ?
                        </a>
                    @endif
                </div>

                <button type="submit"
                        class="btn btn-warning rounded-pill fw-bold w-100 mt-4 py-2">
                    Se connecter
                </button>
            </form>

        </div>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);

            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = 'Masquer';
            } else {
                input.type = 'password';
                button.textContent = 'Afficher';
            }
        }
    </script>
</x-guest-layout>
