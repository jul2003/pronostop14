@extends('layouts.pronos')

@section('content')

<div class="mb-4">
    <a href="{{ route('admin.seasons.index') }}"
       class="text-decoration-none fw-bold">
        ← Retour aux saisons
    </a>

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Joueurs — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        Sélectionne les utilisateurs qui participent à cette saison.
    </p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.seasons.players.sync', $season) }}">
    @csrf

    <div class="rugby-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;" class="text-center">Joue</th>
                        <th>Pseudo</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th class="text-center">Rôle</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox"
                                       name="players[]"
                                       value="{{ $user->id }}"
                                       class="form-check-input"
                                       @checked(in_array($user->id, $selectedPlayers))>
                            </td>

                            <td class="fw-bold" style="color: {{ $user->color ?? '#06142f' }}">
                                {{ $user->nickname ?? $user->name }}
                            </td>

                            <td>
                                {{ $user->name }}
                            </td>

                            <td class="text-muted">
                                {{ $user->email }}
                            </td>

                            <td class="text-center">
                                @if($user->role === 'super_admin')
                                    <span class="badge text-bg-warning rounded-pill">Super Admin</span>
                                @elseif($user->role === 'admin')
                                    <span class="badge text-bg-primary rounded-pill">Admin</span>
                                @else
                                    <span class="badge text-bg-success rounded-pill">Joueur</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucun utilisateur disponible.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
        Enregistrer les joueurs
    </button>
</form>

@endsection
