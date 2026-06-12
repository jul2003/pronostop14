<x-app-layout>

<div class="max-w-6xl mx-auto py-8">

    <h1 class="text-3xl font-black mb-6">
        Gestion des matchs
    </h1>

    @forelse($matches as $match)

        <div class="bg-slate-900 text-white rounded-xl p-4 mb-3">

            <div class="flex justify-between">

                <div>
                    {{ $match->homeTeam->name }}
                    vs
                    {{ $match->awayTeam->name }}
                </div>

                <div>
                    {{ $match->round->name }}
                </div>

            </div>

        </div>

    @empty

        <div>
            Aucun match enregistré.
        </div>

    @endforelse

</div>

</x-app-layout>
