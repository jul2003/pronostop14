@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-uppercase text-primary fw-bold small">
            Administration
        </div>

        <h2 class="fw-bold mb-1">
            Modèle de données
        </h2>

        <p class="text-muted mb-0">
            Lecture automatique de la structure réelle de la base : tables, colonnes et clés étrangères.
        </p>
    </div>

    <a href="{{ route('admin.data-model.index') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Rafraîchir
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Connexion
            </div>

            <div class="h5 fw-bold mb-0">
                {{ $driver }}
            </div>

            <div class="text-muted small">
                {{ $database }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Tables
            </div>

            <div class="h5 fw-bold mb-0">
                {{ count($tables) }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="rugby-card p-4 h-100">
            <div class="text-muted small fw-bold text-uppercase mb-1">
                Relations détectées
            </div>

            <div class="h5 fw-bold mb-0">
                {{ count($foreignKeys) }}
            </div>
        </div>
    </div>
</div>

<div class="rugby-card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1">
                Diagramme Mermaid ER
            </h3>

            <p class="text-muted mb-0">
                Tu peux copier ce bloc dans un outil compatible Mermaid pour obtenir un diagramme visuel.
            </p>
        </div>

        <button type="button"
                class="btn btn-outline-primary rounded-pill fw-bold px-4"
                id="copyMermaidButton">
            Copier
        </button>
    </div>

    <pre class="bg-dark text-white rounded-4 p-3 small mb-0 data-model-code"><code id="mermaidCode">{{ $mermaid }}</code></pre>
</div>

<div class="rugby-card p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h3 class="h5 fw-bold mb-1">
                Tables
            </h3>

            <p class="text-muted mb-0">
                Détail des colonnes, clés primaires et relations.
            </p>
        </div>

        <input type="search"
               id="dataModelSearch"
               class="form-control rounded-pill"
               style="max-width: 320px;"
               placeholder="Rechercher une table ou une colonne">
    </div>

    <div class="d-grid gap-3" id="dataModelTables">
        @foreach($tables as $table)
            <div class="border rounded-4 overflow-hidden data-model-table"
                 data-search="{{ strtolower($table['name'].' '.collect($table['columns'])->pluck('name')->implode(' ')) }}">
                <div class="bg-light p-3 border-bottom d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h4 class="h6 fw-bold mb-1">
                            {{ $table['name'] }}
                        </h4>

                        <div class="text-muted small">
                            {{ count($table['columns']) }} colonne(s)
                            ·
                            {{ count($table['foreign_keys']) }} clé(s) étrangère(s)
                            ·
                            référencée par {{ count($table['referenced_by']) }} relation(s)
                        </div>
                    </div>

                    <span class="badge rounded-pill text-bg-primary">
                        Table
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Colonne</th>
                                <th>Type</th>
                                <th class="text-center">Nullable</th>
                                <th class="text-center">Clé</th>
                                <th>Défaut</th>
                                <th>Extra</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($table['columns'] as $column)
                                @php
                                    $foreignKey = collect($table['foreign_keys'])
                                        ->firstWhere('column', $column['name']);
                                @endphp

                                <tr>
                                    <td class="fw-bold">
                                        {{ $column['name'] }}
                                    </td>

                                    <td>
                                        <code>{{ $column['type'] }}</code>
                                    </td>

                                    <td class="text-center">
                                        @if($column['nullable'])
                                            <span class="badge rounded-pill text-bg-secondary">
                                                oui
                                            </span>
                                        @else
                                            <span class="badge rounded-pill text-bg-light border text-dark">
                                                non
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if($column['primary'])
                                            <span class="badge rounded-pill text-bg-warning">
                                                PK
                                            </span>
                                        @endif

                                        @if($foreignKey)
                                            <span class="badge rounded-pill text-bg-info">
                                                FK
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($column['default'] !== null)
                                            <code>{{ $column['default'] }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($column['extra'])
                                            <code>{{ $column['extra'] }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>

                                @if($foreignKey)
                                    <tr class="table-light">
                                        <td colspan="6" class="small text-muted">
                                            Relation :
                                            <strong>{{ $table['name'] }}.{{ $foreignKey['column'] }}</strong>
                                            →
                                            <strong>{{ $foreignKey['referenced_table'] }}.{{ $foreignKey['referenced_column'] }}</strong>
                                            @if(! empty($foreignKey['constraint']))
                                                · contrainte <code>{{ $foreignKey['constraint'] }}</code>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(! empty($table['referenced_by']))
                    <div class="p-3 border-top bg-light">
                        <div class="fw-bold small mb-2">
                            Référencée par
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach($table['referenced_by'] as $reference)
                                <span class="badge rounded-pill text-bg-light border text-dark">
                                    {{ $reference['table'] }}.{{ $reference['column'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

@endsection

@push('styles')
<style>
    .data-model-code {
        max-height: 520px;
        overflow: auto;
    }

    .data-model-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #6c757d;
    }

    .data-model-table code {
        font-size: 0.82rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyButton = document.getElementById('copyMermaidButton');
        const mermaidCode = document.getElementById('mermaidCode');
        const searchInput = document.getElementById('dataModelSearch');

        if (copyButton && mermaidCode) {
            copyButton.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(mermaidCode.innerText);

                    copyButton.textContent = 'Copié';

                    window.setTimeout(function () {
                        copyButton.textContent = 'Copier';
                    }, 1500);
                } catch (error) {
                    copyButton.textContent = 'Copie impossible';

                    window.setTimeout(function () {
                        copyButton.textContent = 'Copier';
                    }, 1500);
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const search = searchInput.value.trim().toLowerCase();

                document.querySelectorAll('.data-model-table').forEach(function (table) {
                    const content = table.dataset.search || '';

                    table.classList.toggle('d-none', search !== '' && !content.includes(search));
                });
            });
        }
    });
</script>
@endpush
