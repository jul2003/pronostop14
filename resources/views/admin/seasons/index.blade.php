@extends('layouts.pronos')

@section('content')

@php
    $activeSeason = $seasons->firstWhere('is_active', true);
    $historicalSeasons = $seasons->filter(fn ($season) => ! $season->is_active)->values();
@endphp

@include('admin.partials.back-link')

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Saisons
        </h2>

        <p class="text-muted mb-0">
            Gère les saisons du championnat.
        </p>
    </div>

    <a href="{{ route('admin.seasons.create') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Ajouter une saison
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="mb-4">
    <h3 class="h5 fw-bold mb-3">
        Saison active
    </h3>

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Saison</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Verrouillage</th>
                        <th class="text-center">Journées</th>
                        <th class="text-center">TOP 14</th>
                        <th class="text-center">PRO D2</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @if($activeSeason)
                        <tr>
                            <td class="fw-bold">
                                {{ $activeSeason->name }}
                            </td>

                            <td class="text-center">
                                <span class="badge text-bg-success rounded-pill">
                                    Active
                                </span>
                            </td>

                            <td class="text-center">
                                @if($activeSeason->is_locked)
                                    <span class="badge text-bg-danger rounded-pill">
                                        Verrouillée
                                    </span>
                                @else
                                    <span class="badge text-bg-light border text-dark rounded-pill">
                                        Ouverte
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->journees_count }}
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->top14_clubs_count }}
                            </td>

                            <td class="text-center">
                                {{ $activeSeason->prod2_clubs_count }}
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.seasons.active.edit') }}"
                                       class="btn btn-sm btn-outline-dark rounded-pill">
                                        Modifier
                                    </a>

                                    @if($activeSeason->is_locked)
                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Joueurs
                                        </span>

                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Clubs
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.active.players') }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Joueurs
                                        </a>

                                        <a href="{{ route('admin.seasons.active.clubs') }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Clubs
                                        </a>
                                    @endif

                                    <a href="{{ route('admin.seasons.active.journees') }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Journées
                                    </a>

                                    @if($activeSeason->is_locked)
                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Barème
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Avant-saison
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Générer journées
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.active.scoring.edit') }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Barème
                                        </a>

                                        <a href="{{ route('admin.seasons.active.preseason.edit') }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Avant-saison
                                        </a>

                                        <form method="POST"
                                              action="{{ route('admin.seasons.generateJournees', $activeSeason) }}">
                                            @csrf

                                            <button class="btn btn-sm btn-outline-success rounded-pill">
                                                Générer journées
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Aucune saison active définie.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div>
    <h3 class="h5 fw-bold mb-3">
        Historique des saisons
    </h3>

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Saison</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Verrouillage</th>
                        <th class="text-center">Journées</th>
                        <th class="text-center">TOP 14</th>
                        <th class="text-center">PRO D2</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($historicalSeasons as $season)
                        <tr>
                            <td class="fw-bold">
                                {{ $season->name }}
                            </td>

                            <td class="text-center">
                                <span class="badge text-bg-secondary rounded-pill">
                                    Inactive
                                </span>
                            </td>

                            <td class="text-center">
                                @if($season->is_locked)
                                    <span class="badge text-bg-danger rounded-pill">
                                        Verrouillée
                                    </span>
                                @else
                                    <span class="badge text-bg-light border text-dark rounded-pill">
                                        Ouverte
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $season->journees_count }}
                            </td>

                            <td class="text-center">
                                {{ $season->top14_clubs_count }}
                            </td>

                            <td class="text-center">
                                {{ $season->prod2_clubs_count }}
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-wrap gap-2">
                                    <a href="{{ route('admin.seasons.edit', $season) }}"
                                       class="btn btn-sm btn-outline-dark rounded-pill">
                                        Modifier
                                    </a>

                                    @if($season->is_locked)
                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Joueurs
                                        </span>

                                        <span class="btn btn-sm btn-outline-primary rounded-pill disabled opacity-50">
                                            Clubs
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.players', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Joueurs
                                        </a>

                                        <a href="{{ route('admin.seasons.clubs', $season) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill">
                                            Clubs
                                        </a>
                                    @endif

                                    <a href="{{ route('admin.seasons.journees', $season) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Journées
                                    </a>

                                    @if($season->is_locked)
                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Barème
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Avant-saison
                                        </span>

                                        <span class="btn btn-sm btn-outline-success rounded-pill disabled opacity-50">
                                            Générer journées
                                        </span>
                                    @else
                                        <a href="{{ route('admin.seasons.scoring.edit', $season) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Barème
                                        </a>

                                        <a href="{{ route('admin.seasons.preseason.edit', $season) }}"
                                           class="btn btn-sm btn-outline-success rounded-pill">
                                            Avant-saison
                                        </a>

                                        <form method="POST"
                                              action="{{ route('admin.seasons.generateJournees', $season) }}">
                                            @csrf

                                            <button class="btn btn-sm btn-outline-success rounded-pill">
                                                Générer journées
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Aucune saison historique pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
