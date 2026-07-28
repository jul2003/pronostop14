@extends('layouts.pronos')

@section('content')

@include('admin.partials.back-link', [
    'href' => route('admin.index'),
    'label' => 'Retour administration',
])

<div class="mb-4">
    <div class="text-uppercase text-primary fw-bold small">
        Administration
    </div>

    <h2 class="fw-bold mb-1">
        Journées
    </h2>

    <p class="text-muted mb-0">
        La gestion des journées se fait depuis la saison active ou depuis une saison précise.
    </p>
</div>

<div class="rugby-card p-4">
    <a href="{{ route('admin.seasons.active.journees') }}"
       class="btn btn-warning rounded-pill fw-bold px-4">
        Ouvrir les journées de la saison active
    </a>
</div>

@endsection
