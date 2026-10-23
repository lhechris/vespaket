<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center">

        <div>
            @if($editingGame)
                <div class="space-y-3 bg-white p-4 rounded-xl shadow mb-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.title') }}</label>
                        <input type="text" wire:model="gameTitle" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.match_date') }}</label>
                        <input type="datetime-local" wire:model="gameDate" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.match_number') }}</label>
                        <input type="text" wire:model="gameNumero" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.location') }}</label>
                        <select wire:model="gamePlaceId" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                            <option value="">-- {{ __('vespaket.select_sport_hall') }} --</option>
                            @foreach($places as $place)
                                <option value="{{ $place->id }}">{{ $place->name }} {{ $place->address ? '— ' . $place->address : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.meet') }}</label>
                        <input type="text" wire:model="gameRendezvous" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.score') }}</label>
                        <input type="text" wire:model="gameScore" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.match_comments') }}</label>
                        <textarea wire:model="gameCommentaire" class="w-full rounded border border-gray-300 px-3 py-2 text-black"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="updateGame()" class="bg-green-600 text-white px-3 py-2 rounded font-semibold hover:bg-green-700">
                            {{ __('actions.save') }}
                        </button>
                        <button wire:click="toggleEditingGame()" class="bg-gray-600 text-white px-3 py-2 rounded font-semibold hover:bg-gray-700">
                            {{ __('actions.cancel') }}
                        </button>
                    </div>
                </div>
            @else
                <h1 class="text-2xl font-bold text-gray-900">
                    <span class="text-lg">{{ $game->numero }}</span>
                    <span>{{ $game->titre }}</span>
                    <span>({{ $game->score}})</span>
                </h1>

                <p class="text-gray-600"> {{ $game->formatdate() }} </p>

                @if($game->place !==NULL)
                <p class="text-gray-600">{{ $game->place->address }}</p>
                <livewire:itineraire
                        :lat="$game->place->lat"
                        :lng="$game->place->lng"
                        :label="$game->place->name"/>
                @endif
                <p class="text-gray-900">
                    {{ __('vespaket.meet') }} : {{ $game->rendezvous }}
                </p>
                <p>
                {{ $game->commentaire }}
                </p>

                <button wire:click="toggleEditingGame()" class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    ✏️ {{ __('actions.edit') }}
                </button>
                <button wire:click="deleteGame()" 
                    wire:confirm="{{ __('vespaket.confirm_delete') }}"
                    class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    🗑️​ {{ __('actions.delete') }}
                </button>
                
            @endif
        </div>
    </div>
    <div>
        <div class="flex gap-2 mb-2">
            <button wire:click="sendNotification()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700">
                {{ __('vespaket.send_notification') }}
            </button>
            @if($game->team->isU11())
            <button wire:click="generateFeuille()"
                    class="bg-green-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-green-700">
                {{ __('vespaket.roadmap') }}
            </button>
            @endif
            <a href="{{ route('team.show', ['team' => $game->team->id ]) }}" 
               class="bg-black text-white px-4 py-2 rounded-xl font-semibold hover:bg-gray-800">
                {{ __('vespaket.back_to_team') }}
            </a>
        </div>

        <div class="bg-white p-3 rounded-xl shadow">
            {{ __('vespaket.selected') }}: {{ $members->where('pivot.selected', true)->count() }}
        </div>

    </div>

    <!-- LISTE -->
    <div class="flex flex-col lg:flex-row gap-4">
        <div>
            <h2>{{ __('vespaket.team_players') }}</h2>
            <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg">
                <thead class="bg-black border-b border-default">
                    <tr>
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.first_name') }}</th>
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.availability') }}</th>
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.selected') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                        <td class="px-2 sm:px-6 py-4">{{ $member->prenom }}</td>
                        <td class="px-2 sm:px-6 py-4 text-black">
                            <button wire:click="setAvailability({{ $member->id }}, 'yes')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'yes' ? 'bg-green-500 text-white' : 'bg-gray-200' }}">
                                ✔️​
                            </button>

                            <button wire:click="setAvailability({{ $member->id }}, 'no')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'no' ? 'bg-red-500 text-white' : 'bg-gray-200' }}">
                            ❌​
                            </button>

                            <button wire:click="setAvailability({{ $member->id }}, 'maybe')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'maybe' ? 'bg-yellow-400' : 'bg-gray-200' }}">
                                ❓
                            </button>
                        </td>
                        <td class="px-2 sm:px-6 py-4">
                            @if($member->pivot->availability === 'yes')
                                <button wire:click="toggleSelection({{ $member->id }})"
                                    class="px-3 py-1 rounded font-semibold
                                    {{ $member->pivot->selected ? 'bg-green-500' : 'bg-red-500' }}">
                                    {{ $member->pivot->selected ? __('Yes') : __('No') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
                    @foreach($members as $member)
                        @if($member->pivot->selected)
                        <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                            <td class="px-2 sm:px-6 py-4">{{ $member->prenom }}</td>
                            @foreach($options as $option)
                                @if( $option->isDisplayTable())
                                    @php($memberOption = $member->gameOptions
                                        ->where('game_option_id', $option->id)
                                        ->where('game_id', $game->id)
                                        ->first())
                                    <td class="px-2 sm:px-6 py-4">
                                        <x-game-member-option-cell :member="$member" :option="$option" :value="$memberOption?->value" />
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

    <!-- Notifications -->
    <div>
        <textarea rows="6" class="w-96 border rounded p-2" wire:model="message" ></textarea>
        <button        
            wire:click="copyAndOpenWhatsapp"
            class="px-4 py-2 bg-green-600 text-white rounded"
        >
            {{ __('vespaket.copy_open_whatsapp') }}
        </button>
        <span>{{ __('vespaket.description_whatsapp') }}</span>
    </div>

    @script
    <script>
        $wire.on('copy-and-open-whatsapp', async (event) => {

            try {
                await navigator.clipboard.writeText(event.message);

                window.open(event.link, '_blank');
            } catch (e) {
                alert(@js(__('vespaket.unable_copy')));
            }
        });
    </script>
    @endscript    


    <h2>{{ __('vespaket.statistics') }}</h2>
        @foreach($members as $member)
        <x-player-stats :player="$member" :options="$options" />
        @endforeach
</div>


