@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Tableau de bord
    </h2>

    <p class="text-muted mb-0">
        Gère les données du championnat de pronostics.
    </p>
</div>

<div class="row g-4">

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.seasons.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">01</div>
                <div class="h4 fw-bold mb-2">Saisons</div>
                <p class="text-secondary mb-0">
                    Gérer les saisons, clubs participants, journées, matchs, résultats et barèmes.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.clubs.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">02</div>
                <div class="h4 fw-bold mb-2">Clubs</div>
                <p class="text-secondary mb-0">
                    Créer et modifier les clubs TOP 14 et PRO D2.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">03</div>
                <div class="h4 fw-bold mb-2">Utilisateurs</div>
                <p class="text-secondary mb-0">
                    Gérer les joueurs, admins, pseudos, couleurs et activité.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.settings.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">04</div>
                <div class="h4 fw-bold mb-2">Barèmes & journées</div>
                <p class="text-secondary mb-0">
                    Configurer les barèmes globaux et les associations par type de journée.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.settings.preseason') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">05</div>
                <div class="h4 fw-bold mb-2">Paramètres avant-saison</div>
                <p class="text-secondary mb-0">
                    Gérer les questions avant-saison, les bonus conditionnels et les règles de stop.
                </p>
            </div>
        </a>
    </div>

</div>

@endsection
