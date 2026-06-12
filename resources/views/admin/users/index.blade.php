@extends('layouts.pronos')

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">

    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Gestion des utilisateurs
        </h2>

        <p class="text-muted mb-0">
            Gère les joueurs, administrateurs et leurs droits.
        </p>
    </div>

    <a href="{{ route('admin.users.create') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        + Utilisateur
    </a>

</div>

<x-admin-card class="p-0 overflow-hidden">
    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">
                <tr>
                    <th>Pseudo</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Dernière connexion</th>
                    <th class="text-center">Couleur</th>
                    <th class="text-center">Rôle</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($users as $user)

                    <tr>

                        <td class="fw-bold"
                            style="color: {{ $user->color ?? '#06142f' }}">
                            {{ $user->display_name }}
                        </td>

                        <td>
                            {{ $user->name }}
                        </td>

                        <td class="text-muted">
                            {{ $user->email }}
                        </td>

                        <td>
                            @if(!$user->last_login_at)

                                <span class="badge text-bg-secondary">
                                    Jamais connecté
                                </span>

                            @elseif($user->last_login_at->gt(now()->subDays(7)))

                                <span class="badge text-bg-success">
                                    Actif
                                </span>

                            @elseif($user->last_login_at->gt(now()->subDays(30)))

                                <span class="badge text-bg-warning">
                                    Peu actif
                                </span>

                            @else

                                <span class="badge text-bg-danger">
                                    Inactif
                                </span>

                            @endif
                        </td>

                        <td class="text-center">

                            <span class="d-inline-block rounded-circle border"
                                  style="
                                    width:24px;
                                    height:24px;
                                    background:{{ $user->color ?? '#ffd200' }};
                                  ">
                            </span>

                        </td>

                        <td class="text-center">

                            @if($user->isSuperAdmin())

                                <span class="badge rounded-pill text-bg-warning">
                                    👑 Super Admin
                                </span>

                            @elseif($user->role === 'admin')

                                <span class="badge rounded-pill text-bg-primary">
                                    🛠️ Admin
                                </span>

                            @else

                                <span class="badge rounded-pill text-bg-success">
                                    🏉 Joueur
                                </span>

                            @endif

                        </td>

                        <td class="text-end">

                            <div class="d-flex justify-content-end gap-2">

                                @if(!$user->isSuperAdmin())

                                    @if($user->role === 'admin')

                                        <form method="POST"
                                              action="{{ route('admin.users.updateRole', $user) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden"
                                                   name="role"
                                                   value="player">

                                            <button class="btn btn-sm btn-outline-warning rounded-pill">
                                                Rétrograder
                                            </button>
                                        </form>

                                    @else

                                        <form method="POST"
                                              action="{{ route('admin.users.updateRole', $user) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden"
                                                   name="role"
                                                   value="admin">

                                            <button class="btn btn-sm btn-outline-success rounded-pill">
                                                Promouvoir
                                            </button>
                                        </form>

                                    @endif

                                @else

                                    <span class="text-muted small">
                                        Verrouillé
                                    </span>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6"
                            class="text-center text-muted py-4">
                            Aucun utilisateur.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>
</x-admin-card>

@endsection
