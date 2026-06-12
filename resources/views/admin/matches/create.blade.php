<x-app-layout>
<div class="max-w-3xl mx-auto py-8">

    <h1 class="text-3xl font-black mb-6">
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-100 p-4 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        Ajouter un match
    </h1>

    <form method="POST" action="{{ route('admin.matches.store') }}" class="space-y-5">
        @csrf

        <div>
            <label>Journée</label>
            <select name="journee_id"
                    class="w-full rounded text-black"
                    onchange="window.location='{{ route('admin.matches.create') }}?journee_id=' + this.value">
                @foreach($journees as $journee)
                    <option value="{{ $journee->id }}" @selected($selectedJourneeId == $journee->id)>
                        {{ $journee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Équipe domicile</label>
            <select id="home_team_id" name="home_team_id" class="w-full rounded text-black">
                <option value="">Choisir une équipe</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}" @selected(old('home_team_id') == $team->id)>
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Équipe extérieur</label>
            <select id="away_team_id" name="away_team_id" class="w-full rounded text-black">
                <option value="">Choisir une équipe</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}" @selected(old('away_team_id') == $team->id)>
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="rounded-full bg-yellow-400 px-6 py-3 font-bold text-slate-950">
            Créer le match
        </button>
    </form>

    <div class="mt-10 rounded-2xl bg-slate-900 p-6 text-white">
        <h2 class="mb-4 text-xl font-black">
            Matchs déjà créés
        </h2>

        @forelse($existingMatches as $existingMatch)
            <div class="flex items-center justify-between border-b border-slate-700 py-3">
                <div>
                    {{ $loop->iteration }}.
                    {{ $existingMatch->homeTeam->name }}
                    -
                    {{ $existingMatch->awayTeam->name }}
                </div>
            </div>
        @empty
            <p class="text-slate-300">
                Aucun match pour cette journée.
            </p>
        @endforelse
    </div>

</div>

<script>
    const homeSelect = document.getElementById('home_team_id');
    const awaySelect = document.getElementById('away_team_id');

    function updateTeamOptions() {
        const homeValue = homeSelect.value;
        const awayValue = awaySelect.value;

        [...homeSelect.options].forEach(option => {
            option.hidden = option.value !== '' && option.value === awayValue;
        });

        [...awaySelect.options].forEach(option => {
            option.hidden = option.value !== '' && option.value === homeValue;
        });
    }

    homeSelect.addEventListener('change', updateTeamOptions);
    awaySelect.addEventListener('change', updateTeamOptions);

    updateTeamOptions();
</script>

</x-app-layout>
