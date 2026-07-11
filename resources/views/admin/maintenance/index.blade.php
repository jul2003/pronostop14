@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link')

@php
    $commandText = fn ($command) => trim(($command['output'] ?? '') ?: ($command['error_output'] ?? ''));
    $commandOk = fn ($command) => (bool) ($command['success'] ?? false);

    $composerOutdatedPackages = data_get($audit, 'composer.outdated.packages', []);
    $npmOutdatedPackages = data_get($audit, 'npm.outdated.packages', []);

    $composerAuditCount = (int) data_get($audit, 'composer.audit.advisory_count', 0);
    $composerAbandoned = data_get($audit, 'composer.audit.abandoned', []);

    $npmVulnerabilities = data_get($audit, 'npm.audit.json.metadata.vulnerabilities', []);
    $npmVulnerabilitiesTotal = $npmVulnerabilities['total'] ?? null;

    $gitBranchOutput = $commandText(data_get($audit, 'git.branch', []));
    $currentBranch = $gitBranchOutput ?: 'branche-inconnue';
    $maintenanceBranch = str_starts_with($currentBranch, 'maintenance/')
        ? $currentBranch
        : 'maintenance/dependances';

    $gitStatusOutput = $commandText(data_get($audit, 'git.status', []));
    $gitIsClean = $gitStatusOutput === '';

    $composerAvailable = $commandOk(data_get($audit, 'composer.version', []));
    $nodeAvailable = $commandOk(data_get($audit, 'node.version', []));
    $npmAvailable = $commandOk(data_get($audit, 'node.npm', []));

    $composerAuditOk = $composerAuditCount === 0 && $commandOk(data_get($audit, 'composer.audit', []));
    $npmAuditOk = $npmVulnerabilitiesTotal !== null && (int) $npmVulnerabilitiesTotal === 0;

    $maintenanceAuditLooksOk = $composerAvailable
        && $nodeAvailable
        && $npmAvailable
        && $composerAuditOk
        && $npmAuditOk;
@endphp

<div id="page-top" class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Maintenance
        </h2>

        <p class="text-muted mb-0">
            Audit des versions, dépendances Composer/NPM et état Git du projet.
        </p>
    </div>

    <a href="{{ route('admin.maintenance.index') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Rafraîchir l’audit
    </a>
</div>

<div class="alert alert-info">
    <div class="fw-bold">
        Lecture seule pour le moment
    </div>

    Cette page vérifie l’état du projet, mais ne lance aucune mise à jour automatiquement.
    Les commandes proposées restent à exécuter manuellement tant qu’on n’a pas ajouté les actions sécurisées.
</div>

@if(app()->environment('production'))
    <div class="alert alert-danger">
        <div class="fw-bold">
            Environnement production détecté
        </div>

        Les mises à jour Composer/NPM ne devront pas être lancées directement depuis l’interface web en production.
    </div>
@endif

<div class="rugby-card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1">
                Verdict rapide
            </h3>

            <p class="text-muted mb-0">
                Résumé de l’état détecté par l’audit maintenance.
            </p>
        </div>

        @if($maintenanceAuditLooksOk)
            <span class="badge rounded-pill text-bg-success px-3 py-2">
                Audit favorable
            </span>
        @else
            <span class="badge rounded-pill text-bg-warning px-3 py-2">
                Vérifications nécessaires
            </span>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <div class="border rounded-4 p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">
                    Composer
                </div>

                @if($composerAvailable)
                    <span class="badge rounded-pill text-bg-success">
                        Disponible
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger">
                        Indisponible
                    </span>
                @endif
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="border rounded-4 p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">
                    Audit Composer
                </div>

                @if($composerAuditOk)
                    <span class="badge rounded-pill text-bg-success">
                        OK
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger">
                        À corriger
                    </span>
                @endif

                <div class="text-muted small mt-2">
                    {{ $composerAuditCount }} alerte(s)
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="border rounded-4 p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">
                    Node / NPM
                </div>

                @if($nodeAvailable && $npmAvailable)
                    <span class="badge rounded-pill text-bg-success">
                        Disponible
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger">
                        Indisponible
                    </span>
                @endif
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="border rounded-4 p-3 h-100">
                <div class="text-muted small fw-bold text-uppercase mb-1">
                    Audit NPM
                </div>

                @if($npmAuditOk)
                    <span class="badge rounded-pill text-bg-success">
                        OK
                    </span>
                @elseif($npmVulnerabilitiesTotal === null)
                    <span class="badge rounded-pill text-bg-secondary">
                        Indisponible
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger">
                        À corriger
                    </span>
                @endif

                <div class="text-muted small mt-2">
                    {{ $npmVulnerabilitiesTotal ?? 'Non calculé' }} alerte(s)
                </div>
            </div>
        </div>
    </div>

    <div class="alert {{ $maintenanceAuditLooksOk ? 'alert-success' : 'alert-warning' }} mt-4 mb-0">
        @if($maintenanceAuditLooksOk)
            <div class="fw-bold">
                L’audit ne détecte pas de blocage évident.
            </div>

            Tu peux suivre la procédure “Si tout est OK” après avoir vérifié que
            <code>php artisan test</code> et <code>npm run build</code> passent bien dans le terminal.
        @else
            <div class="fw-bold">
                Ne merge pas encore sans vérifier.
            </div>

            Corrige d’abord les alertes affichées plus bas, puis relance
            <code>php artisan test</code> et <code>npm run build</code>.
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                PHP
            </div>

            <div class="h4 fw-bold mb-0">
                {{ data_get($audit, 'php.version') }}
            </div>

            <div class="text-muted small mt-2">
                SAPI : {{ data_get($audit, 'php.sapi') }}
            </div>

            @if(data_get($audit, 'php.binary'))
                <div class="text-muted small mt-1">
                    Binaire : {{ data_get($audit, 'php.binary') }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Laravel
            </div>

            <div class="h4 fw-bold mb-0">
                {{ data_get($audit, 'laravel.version') }}
            </div>

            <div class="text-muted small mt-2">
                Environnement : {{ data_get($audit, 'app.environment') }}
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Composer
            </div>

            @php
                $composerVersion = $commandText(data_get($audit, 'composer.version', []));
            @endphp

            <div class="fw-bold mb-0">
                {{ $composerVersion ?: 'Indisponible' }}
            </div>

            @if(! $commandOk(data_get($audit, 'composer.version', [])))
                <div class="text-danger small mt-2">
                    Composer n’a pas répondu correctement.
                </div>
            @endif
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Node / NPM
            </div>

            <div class="fw-bold">
                Node : {{ $commandText(data_get($audit, 'node.version', [])) ?: 'Indisponible' }}
            </div>

            <div class="text-muted small">
                NPM : {{ $commandText(data_get($audit, 'node.npm', [])) ?: 'Indisponible' }}
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        État Git
                    </h3>

                    <p class="text-muted mb-0">
                        Avant une mise à jour, il vaut mieux avoir un dépôt propre.
                    </p>
                </div>

                @if($gitIsClean)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">
                        Dépôt propre
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-warning px-3 py-2">
                        Modifications locales
                    </span>
                @endif
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-1">
                            Branche
                        </div>

                        <div class="fw-bold">
                            {{ $currentBranch }}
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="border rounded-4 p-3 h-100">
                        <div class="text-muted small fw-bold text-uppercase mb-2">
                            Git status
                        </div>

                        @if($gitIsClean)
                            <div class="text-success fw-bold">
                                Aucune modification locale détectée.
                            </div>
                        @else
                            <pre class="small mb-0 bg-light rounded-3 p-3 border">{{ $gitStatusOutput }}</pre>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <h3 class="h5 fw-bold mb-3">
                Paquets Composer principaux
            </h3>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Paquet</th>
                            <th>Version installée</th>
                            <th>État</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach(data_get($audit, 'composer.packages', []) as $packageName => $package)
                            <tr>
                                <td class="fw-bold">
                                    {{ $packageName }}
                                </td>

                                <td>
                                    {{ $package['version'] ?? 'Indisponible' }}
                                </td>

                                <td>
                                    @if($package['success'] ?? false)
                                        <span class="badge rounded-pill text-bg-success">
                                            OK
                                        </span>
                                    @else
                                        <span class="badge rounded-pill text-bg-danger">
                                            Erreur
                                        </span>

                                        <div class="text-muted small mt-1">
                                            {{ $commandText($package) ?: 'Commande impossible.' }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Mises à jour Composer disponibles
                    </h3>

                    <p class="text-muted mb-0">
                        Résultat de <code>composer outdated --direct</code>.
                    </p>
                </div>

                @if(count($composerOutdatedPackages) === 0)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">
                        Rien à signaler
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-warning px-3 py-2">
                        {{ count($composerOutdatedPackages) }} paquet(s)
                    </span>
                @endif
            </div>

            @if(! empty(data_get($audit, 'composer.outdated.error_output')))
                <div class="alert alert-warning">
                    <div class="fw-bold">
                        Composer outdated a retourné un message
                    </div>

                    <pre class="small mb-0 mt-2">{{ data_get($audit, 'composer.outdated.error_output') }}</pre>
                </div>
            @endif

            @if(count($composerOutdatedPackages) === 0)
                <div class="text-muted">
                    Aucun paquet direct dépassé détecté, ou la commande n’a rien retourné.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Paquet</th>
                                <th>Installé</th>
                                <th>Dernier</th>
                                <th>Statut</th>
                                <th>Description</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($composerOutdatedPackages as $package)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $package['name'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $package['version'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $package['latest'] ?? '-' }}
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill text-bg-light border text-dark">
                                            {{ $package['latest-status'] ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="text-muted small">
                                        {{ $package['description'] ?? '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Audit sécurité Composer
                    </h3>

                    <p class="text-muted mb-0">
                        Résultat de <code>composer audit</code>.
                    </p>
                </div>

                @if($composerAuditCount === 0)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">
                        Aucune vulnérabilité
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger px-3 py-2">
                        {{ $composerAuditCount }} alerte(s)
                    </span>
                @endif
            </div>

            @if($composerAuditCount === 0 && empty($composerAbandoned))
                <div class="text-muted">
                    Aucun avis de sécurité Composer détecté.
                </div>
            @else
                @if($composerAuditCount > 0)
                    <pre class="small bg-light rounded-3 p-3 border">{{ json_encode(data_get($audit, 'composer.audit.json.advisories'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif

                @if(! empty($composerAbandoned))
                    <div class="alert alert-warning mt-3 mb-0">
                        <div class="fw-bold">
                            Paquets abandonnés
                        </div>

                        <pre class="small mb-0 mt-2">{{ json_encode($composerAbandoned, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Mises à jour NPM disponibles
                    </h3>

                    <p class="text-muted mb-0">
                        Résultat de <code>npm outdated</code>.
                    </p>
                </div>

                @if(count($npmOutdatedPackages) === 0)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">
                        Rien à signaler
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-warning px-3 py-2">
                        {{ count($npmOutdatedPackages) }} paquet(s)
                    </span>
                @endif
            </div>

            @if(data_get($audit, 'npm.outdated.skipped'))
                <div class="alert alert-warning mb-0">
                    {{ data_get($audit, 'npm.outdated.reason') }}
                </div>
            @elseif(count($npmOutdatedPackages) === 0)
                <div class="text-muted">
                    Aucun paquet NPM dépassé détecté, ou la commande n’a rien retourné.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Paquet</th>
                                <th>Installé</th>
                                <th>Wanted</th>
                                <th>Dernier</th>
                                <th>Type</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($npmOutdatedPackages as $package)
                                <tr>
                                    <td class="fw-bold">
                                        {{ $package['name'] }}
                                    </td>

                                    <td>
                                        {{ $package['current'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $package['wanted'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $package['latest'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $package['type'] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(! empty(data_get($audit, 'npm.outdated.error_output')))
                <div class="alert alert-warning mt-3 mb-0">
                    <div class="fw-bold">
                        Message NPM
                    </div>

                    <pre class="small mb-0 mt-2">{{ data_get($audit, 'npm.outdated.error_output') }}</pre>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Audit sécurité NPM
                    </h3>

                    <p class="text-muted mb-0">
                        Résultat de <code>npm audit</code>.
                    </p>
                </div>

                @if($npmVulnerabilitiesTotal === null)
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">
                        Indisponible
                    </span>
                @elseif((int) $npmVulnerabilitiesTotal === 0)
                    <span class="badge rounded-pill text-bg-success px-3 py-2">
                        Aucune vulnérabilité
                    </span>
                @else
                    <span class="badge rounded-pill text-bg-danger px-3 py-2">
                        {{ $npmVulnerabilitiesTotal }} alerte(s)
                    </span>
                @endif
            </div>

            @if(data_get($audit, 'npm.audit.skipped'))
                <div class="alert alert-warning mb-0">
                    {{ data_get($audit, 'npm.audit.reason') }}
                </div>
            @elseif($npmVulnerabilitiesTotal === null)
                <div class="alert alert-warning mb-0">
                    L’audit NPM n’a pas retourné de résumé exploitable.

                    @if(! empty(data_get($audit, 'npm.audit.error_output')))
                        <pre class="small mb-0 mt-2">{{ data_get($audit, 'npm.audit.error_output') }}</pre>
                    @endif
                </div>
            @else
                <div class="row g-2">
                    @foreach(['critical', 'high', 'moderate', 'low', 'info', 'total'] as $level)
                        <div class="col-6 col-md-2">
                            <div class="border rounded-4 p-3 text-center h-100">
                                <div class="text-muted small text-uppercase fw-bold">
                                    {{ $level }}
                                </div>

                                <div class="h4 fw-bold mb-0">
                                    {{ $npmVulnerabilities[$level] ?? 0 }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="rugby-card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h5 fw-bold mb-1">
                        Procédure après mise à jour
                    </h3>

                    <p class="text-muted mb-0">
                        À suivre après avoir lancé les mises à jour Composer/NPM sur une branche de maintenance.
                    </p>
                </div>

                <span class="badge rounded-pill text-bg-primary px-3 py-2">
                    Branche actuelle : {{ $currentBranch }}
                </span>
            </div>

            <div class="alert alert-secondary">
                <div class="fw-bold">
                    Commandes de validation à lancer avant toute fusion
                </div>

                <pre class="bg-dark text-white rounded-4 p-3 small mt-3 mb-0"><code>php artisan optimize:clear
php artisan test
npm run build</code></pre>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border border-success rounded-4 p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-success">
                                OK
                            </span>

                            <h4 class="h6 fw-bold mb-0">
                                Si tout passe
                            </h4>
                        </div>

                        <p class="text-muted small">
                            Tests OK, build OK, application vérifiée rapidement dans le navigateur.
                        </p>

                        <pre class="bg-dark text-white rounded-4 p-3 small mb-0"><code>git status
git add .
git commit -m "Update dependencies and maintenance checks"

git checkout main
git merge {{ $maintenanceBranch }}

php artisan test
npm run build

git branch -d {{ $maintenanceBranch }}
git push origin main</code></pre>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border border-warning rounded-4 p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-warning">
                                Pas OK
                            </span>

                            <h4 class="h6 fw-bold mb-0">
                                Si ça casse avant commit
                            </h4>
                        </div>

                        <p class="text-muted small">
                            À utiliser seulement si tu veux jeter les modifications de la branche de maintenance.
                        </p>

                        <pre class="bg-dark text-white rounded-4 p-3 small mb-0"><code>git status
git reset --hard
git clean -fd

git checkout main
git branch -D {{ $maintenanceBranch }}

composer install
npm install
php artisan optimize:clear</code></pre>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border border-danger rounded-4 p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-danger">
                                Rollback
                            </span>

                            <h4 class="h6 fw-bold mb-0">
                                Si le merge est fait mais pas encore poussé
                            </h4>
                        </div>

                        <p class="text-muted small">
                            Revenir à l’état de <code>main</code> avant le merge local.
                        </p>

                        <pre class="bg-dark text-white rounded-4 p-3 small mb-0"><code>git checkout main
git reset --hard ORIG_HEAD

composer install
npm install
php artisan optimize:clear
php artisan test
npm run build</code></pre>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border border-danger rounded-4 p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill text-bg-danger">
                                Après push
                            </span>

                            <h4 class="h6 fw-bold mb-0">
                                Si c’est déjà poussé sur GitHub
                            </h4>
                        </div>

                        <p class="text-muted small">
                            Ne fais pas de <code>reset --hard</code> sur une branche déjà partagée.
                            Il faut créer un commit de revert.
                        </p>

                        <pre class="bg-dark text-white rounded-4 p-3 small mb-0"><code>git checkout main
git pull origin main

git log --oneline

git revert &lt;SHA_DU_COMMIT_A_ANNULER&gt;
php artisan test
npm run build

git push origin main</code></pre>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning mt-4 mb-0">
                <div class="fw-bold">
                    Attention
                </div>

                <code>git reset --hard</code> et <code>git clean -fd</code> suppriment les modifications locales non commités.
                À utiliser uniquement sur une branche de maintenance quand tu es sûr de vouloir tout jeter.
            </div>
        </div>
    </div>
</div>

<button type="button"
        id="backToTopButton"
        class="btn btn-primary rounded-circle shadow position-fixed d-none"
        style="right: 1.25rem; bottom: 1.25rem; z-index: 1050; width: 3rem; height: 3rem;"
        aria-label="Retour en haut"
        title="Retour en haut">
    ↑
</button>

@endsection

@push('scripts')
<script>
    function setupBackToTopButton() {
        const button = document.getElementById('backToTopButton');

        if (!button) {
            return;
        }

        function refreshButtonVisibility() {
            if (window.scrollY > 350) {
                button.classList.remove('d-none');
            } else {
                button.classList.add('d-none');
            }
        }

        button.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', refreshButtonVisibility, {
            passive: true
        });

        refreshButtonVisibility();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupBackToTopButton();
    });
</script>
@endpush
