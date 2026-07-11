@extends('layouts.pronos')

@section('content')

<section class="rugby-home-hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <div class="text-uppercase fw-bold text-warning small mb-2">
                PRONOS TOP 14
            </div>

            <h1 class="display-4 fw-black text-white mb-3">
                Bienvenue {{ auth()->user()->display_name }}
            </h1>

            <p class="lead text-white-50 mb-4">
                Pronostique les journées, suis les résultats et grimpe au classement général.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('pronos.index') }}"
                   class="btn btn-warning rounded-pill fw-bold px-4">
                    Mes pronos
                </a>

                @if($season)
                    <a href="{{ route('rankings.index') }}"
                       class="btn btn-outline-primary rounded-pill fw-bold px-4">
                        Classement
                    </a>

                    <a href="{{ route('seasons.active.results') }}"
                       class="btn btn-outline-primary rounded-pill fw-bold px-4">
                        Résultats
                    </a>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rugby-home-panel">
                <div class="small text-uppercase fw-bold text-warning mb-3">
                    Mon espace
                </div>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="d-inline-flex rounded-circle border border-light"
                          style="width: 48px; height: 48px; background: {{ auth()->user()->color ?? '#ffd200' }}"></span>

                    <div>
                        <div class="h5 fw-bold text-white mb-0">
                            {{ auth()->user()->display_name }}
                        </div>

                        <div class="small text-white-50">
                            {{ auth()->user()->role }}
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('player-profile.edit') }}"
                       class="btn btn-light rounded-pill fw-bold">
                        Modifier mon profil
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}"
                           class="btn btn-outline-warning rounded-pill fw-bold">
                            Administration
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-md-4">
        <a href="{{ route('pronos.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card">
                <div class="feature-number">01</div>

                <div class="h4 fw-bold mb-2">
                    Pronostics
                </div>

                <p class="text-secondary mb-0">
                    Saisis tes choix pour chaque journée.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="{{ route('rankings.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card">
                <div class="feature-number">02</div>

                <div class="h4 fw-bold mb-2">
                    Classement
                </div>

                <p class="text-secondary mb-0">
                    Suis le général et les scores journée.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="{{ route('player-profile.edit') }}" class="text-decoration-none">
            <div class="rugby-feature-card">
                <div class="feature-number">03</div>

                <div class="h4 fw-bold mb-2">
                    Profil
                </div>

                <p class="text-secondary mb-0">
                    Modifie ton pseudo et ta couleur.
                </p>
            </div>
        </a>
    </div>
</div>

@endsection
