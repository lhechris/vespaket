<div>
    <div class="flex gap-4 mb-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.matchs') }}</h2>
        <a href="{{ route('team.games.create', ['team' => $team->id ]) }}" wire:navigate>
            <x-button>➕️{{ __('actions.add') }}</x-button>
        </a>
    </div>

    <x-cards-scroll nextElementId="game-{{ $nextGameId }}" >
        @forelse($games as $game)
            <x-card title="{{$game->titre}}" 
                    id="game-{{ $game->id }}" 
                    href="{{ route('game.edit', [ 'game' => $game->id]) }}"
                    description="{{ $game->formatdate() }}" >
                <p>{{ __('vespaket.meet') }} : {{ $game->rendezvous }}</p>
                <p>{{ $game->members_count }} {{ __('vespaket.players')}}</p>
            </x-card>
        @empty
            <p class="text-gray-500">{{ __('vespaket.no_match') }}</p>
        @endforelse
    </x-cards-scroll>
</div>
