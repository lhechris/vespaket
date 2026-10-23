<div>

    <div class="flex gap-4">
        <table class="w-full text-sm text-center text-body text-yellow-400">
            <thead class="bg-black border-b border-default">
                <tr>
                    <th scope="col" class="px-2 py-3 font-medium"></th>
                    <th scope="col" class="px-2 py-3 font-medium"></th>
                    @foreach($members as $player)
                    <th scope="col" class="px-2 py-3 font-medium h-20 align-bottom">
                        <!--<p class="rotate-[60deg] origin-bottom-left whitespace-nowrap">{{ $player->prenom }}</p>-->
                        <p>{{ $player->prenom }}</p>
                        <p>{{ $player->games_count }}</p>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($games as $game)
                <tr class="odd:bg-gray-800 even:bg-gray-900 border-b border-default">
                    <td class="px-2 py-4 text-left">
                        <p class="hidden sm:block">{{ \Carbon\Carbon::parse($game->date)->format('d/m/Y') }}</p>
                        <p class="hidden sm:block">{{ $game->titre }}</p>
                        <p class="sm:hidden">{{ \Carbon\Carbon::parse($game->date)->format('d/m') }}</p>

                    </td>
                    <td class="px-2 py-4">{{ $game->members_count }}</td>
                    @foreach ($members as $player)
                        @php
                            // On cherche le membre dans la collection déjà chargée pour ce match
                            $pivotMember = $game->members->firstWhere('id', $player->id);
                            $selected    = $pivotMember?->pivot->selected;
                            $availability = $pivotMember?->pivot->availability;
                        @endphp
                        <td class="px-3 py-2">
                            @if (is_null($pivotMember))
                                <span class="text-gray-300">➖​</span>
                            @elseif ($availability === 'yes')
                                <input type="checkbox" 
                                       @checked($selected === 1)
                                       wire:change="toggleSelection({{ $game->id }}, {{ $player->id }})"
                                />
                                <!--<span class="text-green-600 font-bold" title="Sélectionné">✅</span>-->
                            @elseif ($availability === 'no')
                                <span class="text-red-500" title="Indisponible">❌</span>
                            @elseif ($availability === 'maybe')
                                <span class="text-yellow-500" title="Peut-être">❓</span>
                            @else
                                <span class="text-gray-400" title="Non renseigné">⏳</span>
                            @endif
                        </td>
                    @endforeach                    
                </tr>
                @empty
                <tr class="text-gray-500">{{ __('vespaket.no_player') }}</tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
