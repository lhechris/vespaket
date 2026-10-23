<div id="event-{{$member->id}}-{{$event->id}}" 
     class="bg-blue-900 border border-yellow-300 p-4 rounded-2xl shadow hover:shadow-lg transition hover:-translate-y-1">
    @if(auth()->user()->isCoach())
    <a href="{{  route('event.edit', ['event' => $event->id]) }}" >
    @else
    <a href="{{  route('event.show', ['event' => $event->id]) }}" >
    @endif
        <div class="flex flex-col justify-between items-center mb-2">
            <div class="flex" >
                <img src="{{asset('images/fete.png') }}" />
                <p class="text-yellow-400 font-semibold">{{ $event->titre }}</p>
            </div>
            <span class="text-yellow-400 px-2 py-1 rounded-lg">
                {{ $event->formatdate() }}
            </span>
        </div>
    </a>

    <div class="mt-3 text-black">
        <div class="flex flex-wrap gap-2">
            <button wire:click="setAvailability( 'yes')"
                class="px-3 py-1 rounded-lg {{ $availability === 'yes' ? 'bg-green-500' : 'bg-gray-600' }} text-white text-sm hover:bg-green-600">
                {{ __('vespaket.present') }}
            </button>

            <button wire:click="setAvailability( 'no')"
                class="px-3 py-1 rounded-lg {{ $availability === 'no' ? 'bg-red-500' : 'bg-gray-600' }} text-white text-sm hover:bg-red-600">
                {{ __('vespaket.absent') }}
            </button>
        </div>
    </div>
</div> 