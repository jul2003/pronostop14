@extends('layouts.pronos')

@section('content')

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Équipes
        </h2>

        <p class="text-muted mb-0">
            Gère les équipes disponibles pour les saisons.
        </p>
    </div>

    <a href="{{ route('admin.clubs.create') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Ajouter une équipe
    </a>
</div>

<div class="rugby-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Nom court</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($clubs as $club)
                    <tr>
                        <td class="fw-bold">
                            {{ $club->name }}
                        </td>

                        <td class="text-muted">
                            {{ $club->short_name }}
                        </td>

                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.clubs.edit', $club) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill">
                                    Modifier
                                </a>

                                <form method="POST"
                                      action="{{ route('admin.clubs.destroy', $club) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="return confirm('Supprimer cette équipe ?')">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            Aucune équipe créée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
