@extends('layouts.pronos')

@section('content')

<x-admin-header
    title="Classement général"
/>

<x-admin-card class="overflow-hidden p-0">

    <table class="w-full text-sm">

        <thead class="bg-slate-800 text-slate-300">
            <tr>
                <th class="px-4 py-3 text-left">
                    Rang
                </th>

                <th class="px-4 py-3 text-left">
                    Joueur
                </th>

                <th class="px-4 py-3 text-center">
                    Points
                </th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-800">

            @foreach($scores as $score)

                <tr>

                    <td class="px-4 py-4 font-black">
                        @if($score['rank'] === 1)
                            🥇
                        @elseif($score['rank'] === 2)
                            🥈
                        @elseif($score['rank'] === 3)
                            🥉
                        @else
                            {{ $score['rank'] }}
                        @endif
                    </td>

                    <td class="px-4 py-4 font-black"
                        style="color: {{ $score['user']->color }}">
                        {{ $score['user']->display_name }}
                    </td>

                    <td class="px-4 py-4 text-center font-black text-yellow-400">
                        {{ $score['total_points'] }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</x-admin-card>

@endsection
