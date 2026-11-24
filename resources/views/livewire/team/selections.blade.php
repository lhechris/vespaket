<div>
    <x-table.wrapper>
        <x-table.thead>
            <tr>
                <x-table.th></x-table.th>
                <x-table.th></x-table.th>
                @foreach($members as $player)
                <x-table.th class="h-20 align-bottom">
                    <!--<p class="rotate-[60deg] origin-bottom-left whitespace-nowrap">{{ $player->licensee->firstname }}</p>-->
                    <p>{{ $player->licensee->firstname }}</p>
                    <p>{{ $player->games_count }}</p>
                </x-table.th>
                @endforeach
            </tr>
        </x-table.thead>
        <tbody>
            @forelse($games as $game)
            <x-table.tr>
                <x-table.td class="text-left">
                    <p class="hidden sm:block">{{ \Carbon\Carbon::parse($game->date)->format('d/m/Y') }}</p>
                    <p class="hidden sm:block">{{ $game->team_home }} - {{ $game->team_away }}</p>
                    <p class="sm:hidden">{{ \Carbon\Carbon::parse($game->date)->format('d/m') }}</p>

                </x-table.td>
                <x-table.td >{{ $game->members_count }}</x-table.td>
                @foreach ($members as $player)
                    @php
                        // On cherche le membre dans la collection déjà chargée pour ce match
                        $pivotMember = $game->members->firstWhere('id', $player->id);
                        $selected    = $pivotMember?->pivot->selected;
                        $availability = $pivotMember?->pivot->availability;
                    @endphp
                    <x-table.td>
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
                    </x-table.td>
                @endforeach                    
            </x-table.tr>
            @empty
            <tr class="text-gray-500">{{ __('vespaket.no_player') }}</tr>
            @endforelse
        </tbody>
    </x-table.wrapper>
</div>
