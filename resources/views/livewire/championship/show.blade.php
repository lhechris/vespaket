<div class="mx-auto max-w-6xl space-y-6">

    @if(!$championship)
        <section class="rounded-2xl bg-slate-800 p-6 text-yellow-300 shadow-sm">
            <h1 class="text-2xl font-bold text-white">{{ $team->name }}</h1>
            <p class="mt-2 text-slate-300">Aucun championnat n’est associé à cette équipe.</p>
        </section>
    @else
        <header class="rounded-2xl bg-slate-800 p-5 text-yellow-300 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-yellow-200">
                        {{ $championship->season }}
                    </p>
                    <h1 class="mt-2 text-2xl font-bold text-white sm:text-3xl">{{ $team->name }}</h1>
                    <p class="mt-1 text-slate-300">
                        {{ $championship->name }}
                        @if($championship->category || $championship->division || $championship->pool)
                            <span class="text-slate-400">
                                · {{ collect([$championship->category, $championship->division, $championship->pool])->filter()->implode(' · ') }}
                            </span>
                        @endif
                    </p>
                    @can('update', $championship)
                        <a
                            href="{{ route('team.championship.edit', [$team, $championship]) }}"
                            wire:navigate
                            class="mt-3 inline-block text-sm font-semibold text-amber-200 hover:text-amber-100"
                        >
                            Modifier le championnat
                        </a>
                    @endcan
                </div>

                @if($championships->count() > 1)
                    <label class="text-sm font-semibold text-slate-200">
                        Championnat
                        <select
                            wire:change="selectChampionship($event.target.value)"
                            class="mt-1 block w-full min-w-56 rounded-lg border border-slate-600 bg-slate-700 px-3 py-2 text-white"
                        >
                            @foreach($championships as $option)
                                <option value="{{ $option->id }}" @selected($option->id === $championship->id)>
                                    {{ $option->name }} — {{ $option->season }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </header>

        <section aria-labelledby="matchday-heading" class="rounded-2xl bg-slate-800 p-5 text-yellow-300 shadow-sm sm:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <h2 id="matchday-heading" class="text-xl font-bold text-white">
                            {{ $matchday?->label ?: ($matchday ? 'Journée '.$matchday->number : 'Rencontres') }}
                        <span class="text-sm font-normal">via score'n'co</span>
                    </h2>

                    @if($matchday?->date)
                        <p class="mt-1 text-sm text-slate-300">{{ $matchday->date->translatedFormat('d F Y') }}</p>
                    @endif
                </div>
                @if($matchday)
                @can('update', $matchday)
                <div>
                    <button wire:click="refreshMatchday({{ $matchday->id }})">refresh</button>
                </div>
                @endcan
                @endif
                @if($matchdays->isNotEmpty())
                    <nav aria-label="Choisir une journée" class="flex w-full items-center gap-2 sm:w-auto">
                        <button
                            type="button"
                            wire:click="previousMatchday"
                            @disabled($matchday?->id === $matchdays->first()?->id)
                            aria-label="Journée précédente"
                            class="shrink-0 rounded-lg bg-slate-700 p-2 text-slate-200 hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L9.06 10l3.71 3.71a.75.75 0 1 1-1.06 1.06l-4.24-4.24a.75.75 0 0 1 0-1.06l4.24-4.24a.75.75 0 0 1 1.08 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <label class="sr-only" for="matchday-select">Choisir une journée</label>
                        <select
                            id="matchday-select"
                            wire:change="selectMatchday($event.target.value)"
                            class="min-w-0 flex-1 rounded-lg border border-slate-600 bg-slate-700 px-3 py-2 text-sm font-semibold text-white sm:flex-none"
                        >
                            @foreach($matchdays as $day)
                                <option value="{{ $day->id }}" @selected($matchday?->id === $day->id)>
                                    {{ $day->label ?: 'Journée '.$day->number }}
                                </option>
                            @endforeach
                        </select>
                        <button
                            type="button"
                            wire:click="nextMatchday"
                            @disabled($matchday?->id === $matchdays->last()?->id)
                            aria-label="Journée suivante"
                            class="shrink-0 rounded-lg bg-slate-700 p-2 text-slate-200 hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.24 4.24a.75.75 0 0 1 0 1.06l-4.24 4.24a.75.75 0 0 1-1.08 0Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </nav>
                @endif
            </div>

            @if($matchday?->games->isNotEmpty())
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($matchday->games as $game)
                        @php($teamInGame = $game->homeTeam?->team_id === $team->id || $game->awayTeam?->team_id === $team->id)
                        <article class="rounded-xl border p-4 {{ $teamInGame ? 'border-amber-300/60 bg-slate-700' : 'border-slate-700 bg-slate-900/40' }}">
                            @if($game->scheduled_at)
                                <p class="mb-3 text-center text-xs text-slate-400">
                                    {{ $game->scheduled_at->translatedFormat('d/m/Y H:i') }}
                                </p>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <span class="min-w-0 flex-1 text-right font-semibold text-xs {{ $game->homeTeam?->team_id === $team->id ? 'text-amber-200' : 'text-white' }}">
                                    {{ $game->homeTeam?->name ?? 'Équipe inconnue' }}
                                </span>
                                @if($game->home_score !== null && $game->away_score !== null)
                                    <span class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 font-bold text-white">
                                        @if($game->home_score == $game->away_score)
                                        {{ $game->home_score }} – {{ $game->away_score }}
                                        @elseif($game->home_score > $game->away_score)
                                        <span class="text-green-500">{{ $game->home_score }}</span> – <span class="text-red-500">{{ $game->away_score }}</span>
                                        @else
                                        <span class="text-red-500">{{ $game->home_score }}</span> – <span class="text-green-500">{{ $game->away_score }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-300">
                                        {{ $game->status === 'postponed' ? 'Reporté' : 'À venir' }}
                                    </span>
                                @endif
                                <span class="min-w-0 flex-1 font-semibold text-xs {{ $game->awayTeam?->team_id === $team->id ? 'text-amber-200' : 'text-white' }}">
                                    {{ $game->awayTeam?->name ?? 'Équipe inconnue' }}
                                </span>
                            </div>
                            @if($game->location)
                                <p class="mt-3 text-center text-xs text-slate-400">{{ $game->location }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-5 rounded-xl bg-slate-900/40 p-4 text-sm text-slate-300">
                    Aucune rencontre enregistrée pour cette journée.
                </p>
            @endif
        </section>

        <section aria-labelledby="standings-heading" class="rounded-2xl bg-slate-800 p-5 text-yellow-300 shadow-sm sm:p-6">
            <div class="mb-4">
                <h2 id="standings-heading" class="text-xl font-bold text-white">Classement</h2>
                @if($snapshot)
                    <p class="mt-1 text-sm text-slate-300">
                        Mis à jour le {{ $snapshot->captured_at->translatedFormat('d/m/Y à H:i') }}
                    </p>
                @endif
            </div>
            @if($standings->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] border-collapse text-left text-sm">
                        <thead class="border-b border-slate-600 text-xs uppercase tracking-wide text-slate-300">
                            <tr>
                                <th scope="col" class="px-3 py-3">Pos.</th>
                                <th scope="col" class="px-3 py-3">Équipe</th>
                                <th scope="col" class="px-3 py-3 text-center">J</th>
                                <th scope="col" class="px-3 py-3 text-center">V</th>
                                <th scope="col" class="px-3 py-3 text-center">D</th>
                                <th scope="col" class="px-3 py-3 text-center">Pts marqués</th>
                                <th scope="col" class="px-3 py-3 text-center">Pts encaissés</th>
                                <th scope="col" class="px-3 py-3 text-center">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($standings as $entry)
                                @php($isCurrentTeam = $entry->championshipTeam?->team_id === $team->id)
                                <tr class="border-b border-slate-700 last:border-0 {{ $isCurrentTeam ? 'bg-amber-300/15 text-amber-100' : 'text-slate-100' }}">
                                    <td class="px-3 py-3 font-semibold">{{ $entry->rank }}</td>
                                    <th scope="row" class="px-3 py-3 font-semibold">
                                        {{ $entry->championshipTeam?->name ?? 'Équipe inconnue' }}
                                    </th>
                                    <td class="px-3 py-3 text-center">{{ $entry->played }}</td>
                                    <td class="px-3 py-3 text-center">{{ $entry->won }}</td>
                                    <td class="px-3 py-3 text-center">{{ $entry->lost }}</td>
                                    <td class="px-3 py-3 text-center">{{ $entry->points_for }}</td>
                                    <td class="px-3 py-3 text-center">{{ $entry->points_against }}</td>
                                    <td class="px-3 py-3 text-center font-bold">{{ $entry->points }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="rounded-xl bg-slate-900/40 p-4 text-sm text-slate-300">
                    Aucun classement n’a encore été enregistré pour ce championnat.
                </p>
            @endif
        </section>
    @endif
</div>
