<x-app-layout>
    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto max-w-7xl px-6 py-10">

            <div class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-800 p-8 shadow-xl">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-yellow-400">
                    TOP 14 PRONOS
                </p>

                <h1 class="mt-4 text-4xl font-black">
                    Bienvenue sur ton championnat des pronos
                </h1>

                <p class="mt-3 max-w-2xl text-slate-300">
                    Saisis tes pronostics, suis les résultats et grimpe au classement général.
                </p>

                <a href="{{ route('pronos.index') }}"
                   class="mt-6 inline-flex rounded-full bg-yellow-400 px-6 py-3 font-bold text-slate-950">
                    Faire mes pronos
                </a>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-900 p-6">
                    <p class="text-sm text-slate-400">Journée active</p>
                    <p class="mt-2 text-3xl font-black">J25</p>
                </div>

                <div class="rounded-2xl bg-slate-900 p-6">
                    <p class="text-sm text-slate-400">Matchs à pronostiquer</p>
                    <p class="mt-2 text-3xl font-black">7</p>
                </div>

                <div class="rounded-2xl bg-slate-900 p-6">
                    <p class="text-sm text-slate-400">Ton classement</p>
                    <p class="mt-2 text-3xl font-black">—</p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
