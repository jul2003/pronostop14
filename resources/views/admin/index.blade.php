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

                <div class="h4 fw-bold mb-2">
                    Saisons
                </div>

                <p class="text-secondary mb-0">
                    Gérer les saisons, clubs participants, journées, matchs, résultats et barèmes.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.upcoming-matches.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">02</div>

                <div class="h4 fw-bold mb-2">
                    Matchs à saisir
                </div>

                <p class="text-secondary mb-0">
                    Préparer les prochaines journées en saisissant les matchs à venir.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.pending-results.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">03</div>

                <div class="h4 fw-bold mb-2">
                    Résultats à saisir
                </div>

                <p class="text-secondary mb-0">
                    Saisir les résultats des journées dont la deadline est dépassée.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.clubs.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">04</div>

                <div class="h4 fw-bold mb-2">
                    Clubs
                </div>

                <p class="text-secondary mb-0">
                    Créer et modifier les clubs TOP 14 et PRO D2.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.users.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">05</div>

                <div class="h4 fw-bold mb-2">
                    Utilisateurs
                </div>

                <p class="text-secondary mb-0">
                    Gérer les joueurs, admins, pseudos, couleurs et activité.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.settings.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">06</div>

                <div class="h4 fw-bold mb-2">
                    Barèmes & journées
                </div>

                <p class="text-secondary mb-0">
                    Gérer les barèmes de matchs et leur association aux types de journées.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.settings.preseason') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">07</div>

                <div class="h4 fw-bold mb-2">
                    Paramètres avant-saison
                </div>

                <p class="text-secondary mb-0">
                    Gérer les questions, barèmes, groupes de correction et bonus avant-saison globaux.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('admin.app-settings.index') }}" class="text-decoration-none">
            <div class="rugby-feature-card h-100">
                <div class="feature-number">08</div>

                <div class="h4 fw-bold mb-2">
                    Paramètres de l’application
                </div>

                <p class="text-secondary mb-0">
                    Gérer les réglages fonctionnels globaux de l’application.
                </p>
            </div>
        </a>
    </div>

    @if(auth()->user()?->isSuperAdmin())
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('admin.maintenance.index') }}" class="text-decoration-none">
                <div class="rugby-feature-card h-100">
                    <div class="feature-number">09</div>

                    <div class="h4 fw-bold mb-2">
                        Maintenance
                    </div>

                    <p class="text-secondary mb-0">
                        Vérifier les versions, dépendances Composer/NPM, audits sécurité et état Git.
                    </p>
                </div>
            </a>
        </div>
    @endif
</div>

@endsection
