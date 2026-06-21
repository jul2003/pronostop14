@extends('layouts.pronos')

@section('content')

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

<div class="rugby-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Saison</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Journées</th>
                    <th class="text-center">TOP 14</th>
                    <th class="text-center">PRO D2</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($seasons as $season)
                    <tr>
                        <td class="fw-bold">
                            {{ $season->name }}
                        </td>

                        <td class="text-center">
                            @if($season->is_active)
                                <span class="badge text-bg-success rounded-pill">
                                    Active
                                </span>
                            @else
                                <span class="badge text-bg-secondary rounded-pill">
                                    Inactive
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
                                <a href="{{ route('admin.seasons.players', $season) }}"
                                class="btn btn-sm btn-outline-primary rounded-pill">
                                    Joueurs
                                </a>

                                <a href="{{ route('admin.seasons.clubs', $season) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill">
                                    Clubs
                                </a>

                                <a href="{{ route('admin.seasons.journees', $season) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-pill">
                                    Journées
                                </a>

                                <a href="{{ route('admin.seasons.edit', $season) }}"
                                   class="btn btn-sm btn-outline-dark rounded-pill">
                                    Modifier
                                </a>

                                <a href="{{ route('admin.seasons.scoring.edit', $season) }}"
                                class="btn btn-sm btn-outline-success rounded-pill">
                                    Barème
                                </a>

                                <form method="POST"
                                    action="{{ route('admin.seasons.generateJournees', $season) }}">
                                    @csrf

                                    <button class="btn btn-sm btn-outline-success rounded-pill">
                                        Générer journées
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Aucune saison créée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
