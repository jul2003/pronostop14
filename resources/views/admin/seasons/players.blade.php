@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.seasons.index'),
    'label' => 'Retour aux saisons',
])

<div class="mb-4">

    <div class="mt-3 text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Joueurs — {{ $season->name }}
    </h2>

    <p class="text-muted mb-0">
        @if($season->is_locked)
            Cette saison est verrouillée. Les joueurs sont consultables uniquement.
        @else
            Sélectionne les utilisateurs qui participent à cette saison, puis déplace les joueurs sélectionnés pour définir leur ordre d’affichage.
        @endif
    </p>
</div>

@if($season->is_locked)
    <div class="alert alert-warning">
        <div class="fw-bold">
            Saison verrouillée
        </div>

        <div>
            Les joueurs de cette saison ne peuvent plus être modifiés.
            Pour corriger la liste des joueurs, il faut d’abord déverrouiller la saison depuis sa page d’édition.
        </div>
    </div>
@endif

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

                <tbody id="playersList">
                    @forelse($users as $user)
                        <tr data-id="{{ $user->id }}">
                            <td class="text-center">
                                <input type="checkbox"
                                       name="players[]"
                                       value="{{ $user->id }}"
                                       class="form-check-input"
                                       @checked(in_array($user->id, $selectedPlayers))
                                       @disabled($season->is_locked)>
                            </td>

                            <td class="fw-bold" style="color: {{ $user->color ?? '#06142f' }}">
                                @if($season->is_locked)
                                    <span class="text-muted me-2">☰</span>
                                @else
                                    <span class="drag-handle text-muted me-2" style="cursor: grab;">☰</span>
                                @endif

                                {{ $user->nickname ?? $user->name }}
                            </td>

                            <td>{{ $user->name }}</td>

                            <td class="text-muted">{{ $user->email }}</td>

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

    @unless($season->is_locked)
        <button class="btn btn-warning rounded-pill fw-bold mt-4 px-4">
            Enregistrer les joueurs
        </button>
    @endunless
</form>

@endsection

@push('scripts')
    @unless($season->is_locked)
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <script>
            window.addEventListener('load', function () {
                const list = document.getElementById('playersList');

                if (!list || !window.Sortable) {
                    return;
                }

                new window.Sortable(list, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'opacity-50',

                    onEnd: function () {
                        const players = [...list.querySelectorAll('tr')]
                            .filter(row => row.querySelector('input[type="checkbox"]').checked)
                            .map(row => row.dataset.id);

                        fetch("{{ route('admin.seasons.players.reorder', $season) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ players }),
                        });
                    },
                });
            });
        </script>
    @endunless
@endpush
