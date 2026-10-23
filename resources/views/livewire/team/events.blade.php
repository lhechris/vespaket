<div>
    <div class="flex gap-4 mb-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.events') }}</h2>
        <a href="{{ route('team.events.create', ['team' => $team->id ]) }}" wire:navigate>
            <x-button>➕️{{ __('actions.add') }}</x-button>
        </a>
    </div>

    <x-cards-scroll nextElementId="event-{{ $nextEventId }}" >
        @forelse($events as $event)
            <x-card title="{{$event->titre}}" 
                    id="event-{{ $event->id }}" 
                    href="{{ route('event.edit', [ 'event' => $event->id]) }}"
                    description="{{ $event->formatdate() }}" >               
                <p>{{ $event->members_count }} {{ __('vespaket.players')}}</p>
            </x-card>
        @empty
            <p class="text-gray-500">{{ __('vespaket.no_event') }}</p>
        @endforelse
    </x-cards-scroll>
</div>
