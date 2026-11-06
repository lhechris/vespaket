<div class="mx-auto max-w-5xl space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Matchs — {{ $gameday->label ?: 'Journée '.$gameday->number }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">{{ $championship->name }} — {{ $championship->season }}</p>
        </div>
        @can('create', \App\Models\ChampionshipGames::class)
            <a
                href="{{ route('team.championship.gamedays.games.create', [$team, $championship, $gameday]) }}"
                wire:navigate
                class="rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-500"
            >
                Ajouter un match
            </a>
        @endcan
    </header>

    @if(session()->has('success'))
        <p role="status" class="rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</p>
    @endif

    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6">
        @if($games->isEmpty())
            <p class="text-sm text-slate-600">Aucun match n’est enregistré pour cette journée.</p>
        @else
            <ul class="divide-y divide-slate-200">
                @foreach($games as $game)
                    <li class="flex flex-col justify-between gap-3 py-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-semibold text-slate-900">
                                {{ $game->homeTeam->name }}
                                @if($game->home_score !== null && $game->away_score !== null)
                                    <span class="mx-2">{{ $game->home_score }} – {{ $game->away_score }}</span>
                                @else
                                    <span class="mx-2 text-slate-500">vs</span>
                                @endif
                                {{ $game->awayTeam->name }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $game->scheduled_at?->translatedFormat('d F Y à H:i') ?? 'Date à définir' }}
                                · {{ match ($game->status) {
                                    'completed' => 'Terminé',
                                    'postponed' => 'Reporté',
                                    'cancelled' => 'Annulé',
                                    default => 'À venir',
                                } }}
                                @if($game->location)
                                    · {{ $game->location }}
                                @endif
                            </p>
                        </div>
                        @can('update', $game)
                            <a
                                href="{{ route('team.championship.gamedays.games.edit', [$team, $championship, $gameday, $game]) }}"
                                wire:navigate
                                class="text-sm font-semibold text-indigo-600 hover:underline"
                            >
                                Modifier
                            </a>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <a
        href="{{ route('team.championship.edit', [$team, $championship]) }}#gamedays"
        wire:navigate
        class="inline-block text-sm font-semibold text-slate-700 hover:underline"
    >
        Retour au championnat
    </a>
</div>
