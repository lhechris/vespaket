<div id="game-{{$member->id}}-{{$game->id}}" class="bg-black border border-yellow-300 p-4 rounded-2xl shadow hover:shadow-lg transition hover:-translate-y-1">
    @if(auth()->user()->isCoach())
    <a href="{{  route('game.edit', ['game' => $game->id]) }}" >
    @else
    <a href="{{  route('game.show', ['game' => $game->id]) }}" >
    @endif
    <div class="flex justify-between items-center mb-2">

        <p class="text-yellow-400 font-semibold">{{ $game->titre }}</p>

        <span class="text-yellow-400 px-2 py-1 rounded-lg">
            {{ $game->formatdate() }}
        </span>
    </div>

    <div class="mt-2">
        @if ($isSelected>=0)
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $isSelected ? 'bg-green-500 text-white' : 'bg-gray-700 text-gray-300' }}">
            {{ $isSelected ? 'Sélectionné' : 'Non sélectionné' }}
        </span>
        @endif
    </div>

    <div class="mt-3 flex justify-between items-center">
        <span class="text-gray-400">{{ __('vespaket.meet') }} : {{ $game->rendezvous }}</span>
    </div>
    </a>
    <div class="mt-3 text-black">
        <div class="flex flex-wrap gap-2">
            <button wire:click="setAvailability({{ $member->id }}, {{ $game->id }}, 'yes')"
                class="px-3 py-1 rounded-lg {{ $availability === 'yes' ? 'bg-green-500' : 'bg-gray-600' }} text-white text-sm hover:bg-green-600">
                {{ __('vespaket.present') }}
            </button>

            <button wire:click="setAvailability({{ $member->id }}, {{ $game->id }}, 'no')"
                class="px-3 py-1 rounded-lg {{ $availability === 'no' ? 'bg-red-500' : 'bg-gray-600' }} text-white text-sm hover:bg-red-600">
                {{ __('vespaket.absent') }}
            </button>
        </div>
    </div>
</div>
