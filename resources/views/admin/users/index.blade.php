@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link')

<div id="page-top" class="d-flex justify-content-between align-items-start mb-4">
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
                    @php
                        $lastLoginAt = $user->last_login_at
                            ? $user->last_login_at->copy()->timezone('Europe/Paris')
                            : null;
                    @endphp

                    <tr>
                        <td class="fw-bold"
                            style="color: {{ $user->color ?? '#06142f' }}">
                            {{ $user->display_name }}
                        </td>

                        <td>
                            {{ $user->name }}
                        </td>

                        <td class="text-muted">
                            <div>
                                @if($user->email_pro)
                                    <div>{{ $user->email_pro }}</div>
                                @endif

                                @if($user->email_perso)
                                    <div class="text-muted small">{{ $user->email_perso }}</div>
                                @endif
                            </div>
                        </td>

                        <td>
                            @if(!$lastLoginAt)
                                <span class="badge text-bg-secondary">
                                    Jamais connecté
                                </span>
                            @else
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @if($lastLoginAt->gt(now('Europe/Paris')->subDays(7)))
                                            <span class="badge text-bg-success">
                                                Actif
                                            </span>
                                        @elseif($lastLoginAt->gt(now('Europe/Paris')->subDays(30)))
                                            <span class="badge text-bg-warning">
                                                Peu actif
                                            </span>
                                        @else
                                            <span class="badge text-bg-danger">
                                                Inactif
                                            </span>
                                        @endif

                                        <span class="text-muted small"
                                              title="Heure de Paris — {{ $lastLoginAt->format('d/m/Y H:i:s') }}">
                                            {{ $lastLoginAt->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <span class="text-muted small">
                                        {{ $lastLoginAt->diffForHumans() }}
                                    </span>
                                </div>
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
                                    Super Admin
                                </span>
                            @elseif($user->role === 'admin')
                                <span class="badge rounded-pill text-bg-primary">
                                    Admin
                                </span>
                            @else
                                <span class="badge rounded-pill text-bg-success">
                                    Joueur
                                </span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @if(auth()->user()->isSuperAdmin() && ! $user->isSuperAdmin())
                                    <form method="POST"
                                          action="{{ route('admin.users.impersonate', $user) }}">
                                        @csrf

                                        <button class="btn btn-sm btn-outline-dark rounded-pill">
                                            Saisir ses pronos
                                        </button>
                                    </form>
                                @endif

                                @if($user->isSuperAdmin())
                                    <span class="text-muted small">
                                        Verrouillé
                                    </span>
                                @elseif($user->role === 'admin')
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"
                            class="text-center text-muted py-4">
                            Aucun utilisateur.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-card>

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
