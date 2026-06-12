@extends('layouts.pronos')

@section('content')
<div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto max-w-6xl px-6 py-10">
        <a href="{{ route('admin') }}"
        class="text-sm text-yellow-400">
            ← Retour au dashboard
        </a>

        <h1 class="mb-8 text-3xl font-black">
            Gestion des journées
        </h1>

        <div class="space-y-4">
            @foreach($journees as $journee)
                <div class="rounded-2xl bg-slate-900 p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xl font-black">
                                {{ $journee->name }}
                            </div>

                            <div class="mt-1 text-sm text-slate-400">
                                Début :
                                {{ $journee->starts_at ?? 'Non défini' }}
                                · Limite pronos :
                                {{ $journee->prediction_deadline ?? 'Non définie' }}
                                · {{ $journee->matches_count }} match(s)
                            </div>
                        </div>

                        <a href="{{ route('admin.journees.matches', $round) }}"
                           class="rounded-full bg-yellow-400 px-5 py-2 font-bold text-slate-950">
                            Gérer les matchs
                        </a>

                        <a href="{{ route('admin.journees.results', $journee) }}"
                        class="rounded-full bg-slate-700 px-5 py-2 font-bold text-white">
                            Saisir les résultats
                        </a>

                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection
