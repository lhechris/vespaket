<section id="gamedays" class="space-y-4 rounded-2xl bg-slate-800 p-5 text-yellow-300 shadow-sm sm:p-6">
    <header class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Journées du championnat</h2>
            <p class="mt-1 text-sm text-slate-300">{{ $championship->name }} — {{ $championship->season }}</p>
        </div>
        @can('create', \App\Models\ChampionshipGamedays::class)
            <a
                href="{{ route('team.championship.gamedays.create', [$team, $championship]) }}"
                wire:navigate
                class="rounded-lg bg-amber-300 px-4 py-2 text-center text-sm font-semibold text-slate-900 hover:bg-amber-200"
            >
                Ajouter une journée
            </a>
        @endcan
    </header>

    @if($gamedays->isEmpty())
        <p class="rounded-lg bg-slate-700 p-4 text-sm text-slate-200">Aucune journée n’est enregistrée pour ce championnat.</p>
    @else
        <ul class="divide-y divide-slate-700">
            @foreach($gamedays as $gameday)
                <li class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="font-semibold text-white">
                            {{ $gameday->label ?: 'Journée '.$gameday->number }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-300">
                            Journée {{ $gameday->number }}
                            @if($gameday->date)
                                · {{ $gameday->date->translatedFormat('d F Y') }}
                            @endif
                            · {{ $gameday->games_count }} match(s)
                        </p>
                        <a
                            href="{{ route('team.championship.gamedays.games.index', [$team, $championship, $gameday]) }}"
                            wire:navigate
                            class="mt-2 inline-block text-sm font-semibold text-amber-200 hover:text-amber-100"
                        >
                            Voir les matchs
                        </a>
                    </div>
                    @can('update', $gameday)
                        <a
                            href="{{ route('team.championship.gamedays.edit', [$team, $championship, $gameday]) }}"
                            wire:navigate
                            class="text-sm font-semibold text-amber-200 hover:text-amber-100"
                        >
                            Modifier
                        </a>
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif
</div>
