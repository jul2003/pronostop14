<x-app-layout>
    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto max-w-5xl px-6 py-10">

            <h1 class="text-4xl font-black">
                Classement — {{ $journee->name }}
            </h1>

            <a href="{{ route('pronos.index') }}"
                class="mt-4 inline-flex text-sm text-yellow-400">
                    ← Retour aux pronos
            </a>

            <div class="mt-8 overflow-hidden rounded-2xl bg-slate-900">
                <table class="w-full text-sm">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Rang</th>
                            <th class="px-4 py-3 text-left">Joueur</th>
                            <th class="px-4 py-3 text-center">Matchs</th>
                            <th class="px-4 py-3 text-center">Bonus</th>
                            <th class="px-4 py-3 text-center">Total</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800">
                        @foreach($scores as $score)
                            <tr>
                                <td class="px-4 py-4 font-black">
                                    {{ $score->rank }}
                                </td>

                                <td class="px-4 py-4 font-bold"
                                    style="color: {{ $score->user->color ?? '#ffffff' }}">
                                    {{ $score->user->display_name }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    {{ $score->match_points }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    {{ $score->perfect_round_bonus }}
                                </td>

                                <td class="px-4 py-4 text-center font-black text-yellow-400">
                                    {{ $score->total_points }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
