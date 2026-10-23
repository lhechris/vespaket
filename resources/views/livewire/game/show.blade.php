<div class="space-y-6 mb-4">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $game->titre }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ $game->formatdate() }}
            </p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if($game->place !==NULL)                        
                <div class="bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-700 text-yellow-300">
                    <p class="text-xs uppercase tracking-[0.16em] font-semibold text-yellow-200">{{ __('vespaket.location') }}</p>
                    <div class="flex gap-2" >
                        <p class="mt-2 text-base font-semibold">{{ $game->place->address }}</p>
                        <livewire:itineraire
                                :lat="$game->place->lat"
                                :lng="$game->place->lng"
                                :label="$game->place->name"/>
                    </div>
                </div>
                @endif
                <div class="bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-700 text-yellow-300">
                    <p class="text-xs uppercase tracking-[0.16em] font-semibold text-yellow-200">{{ __('vespaket.meet') }}</p>
                    <p class="mt-2 text-base font-semibold">{{ $game->rendezvous }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($members as $member)
            <div class="bg-slate-800 p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col gap-4 text-yellow-300">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-yellow-200">{{ $member->prenom }}</p>
                    </div>
                    @if($member->pivot->selected)
                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">{{ __('vespaket.selected') }}</span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <button wire:click="setAvailability({{ $member->id }}, 'yes')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'yes' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{__('vespaket.present')}}
                    </button>
                    <button wire:click="setAvailability({{ $member->id }}, 'no')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'no' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('vespaket.absent') }}
                    </button>
                    <button wire:click="setAvailability({{ $member->id }}, 'maybe')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'maybe' ? 'bg-yellow-400 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('vespaket.maybe') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div>
        <h2>{{ __('vespaket.list_players_selected') }}</h2>
        <table class="text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg ">
            <thead class="bg-black border-b border-default">
                <tr>
                    <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.first_name') }}</th>
                    @foreach($options as $option)
                        @if( $option->isDisplayTable())
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ $option->name }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($players as $player)
                    @if($player->pivot->selected)
                    <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                        <td class="px-2 sm:px-6 py-4">{{ $player->prenom }}</td>
                        @foreach($options as $option)
                            @if( $option->isDisplayTable())
                                @php
                                    $memberOption = $player->gameOptions
                                                        ->where('game_option_id', $option->id)
                                                        ->where('game_id',$game->id)
                                                        ->first();
                                @endphp
                                <td class="px-2 sm:px-6 py-4">
                                    
                                    @if($members->contains('id',$player->id) && ($option->isDisplayTableAllEditable()))
                                    <x-game-member-option-cell :member="$player" :option="$option" :value="$memberOption?->value" />
                                    @elseif ($option->type === \App\Models\GameOption::TYPE_CHECKBOX)
                                    {{ $memberOption?->value === 'yes'?'✅​':''}}
                                    @else
                                    {{ $memberOption?->value }}                                    
                                    @endif
                                    
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>