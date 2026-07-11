<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Pronos TOP 14') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-180x180.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="rugby-page">
<header class="site-header">
    <div class="topbar">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('home') }}" class="brand text-decoration-none">
                <span class="brand-kicker">Pronos</span>
                <span class="brand-title">Top 14</span>
            </a>

            @auth
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('player-profile.edit') }}"
                       class="user-chip text-decoration-none">
                        <span class="user-dot"
                              style="background: {{ auth()->user()->color ?? '#ffd200' }}"></span>
                        <span>{{ auth()->user()->display_name }}</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="logout-link">
                            Déconnexion
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </div>

    @auth
        <nav class="main-nav">
            <div class="container d-flex flex-wrap gap-4">
                <a href="{{ route('home') }}">
                    Accueil
                </a>

                <a href="{{ route('pronos.index') }}">
                    Pronos
                </a>

                <a href="{{ route('rankings.index') }}">
                    Classement général
                </a>

                <a href="{{ route('results.index') }}">
                    Résultats
                </a>

                <a href="{{ route('player-profile.edit') }}">
                    Mon profil
                </a>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.index') }}" class="admin-link">
                        Administration
                    </a>
                @endif
            </div>
        </nav>
    @endauth
</header>

@if(session()->has('impersonator_id'))
    <div class="alert alert-warning rounded-0 mb-0 text-center fw-bold">
        Mode reprise historique :
        vous êtes connecté en tant que
        <strong>{{ auth()->user()->display_name }}</strong>

        <form method="POST"
              action="{{ route('impersonation.stop') }}"
              class="d-inline ms-3">
            @csrf

            <button type="submit"
                    class="btn btn-sm btn-dark rounded-pill">
                Revenir super admin
            </button>
        </form>
    </div>
@endif

<main class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-warning">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = 'Masquer';
        } else {
            input.type = 'password';
            button.innerHTML = 'Afficher';
        }
    }
</script>

@stack('scripts')

</body>
</html>
