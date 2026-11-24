<div class="space-y-6">
    <livewire:news.show />
    <livewire:InstallPrompt />
    <x-button
            id="subscribe-push-button"
         x-show="!{{$hasSubscription ? 'true' : 'false'}}"
         onclick="subscribeToPush()" type="button"> S'abonner</x-button>
        <p id="push-subscription-status" class="mt-2 text-sm" role="status" aria-live="polite"></p>
    <!-- MEMBRES -->
    @if(count($licensees)>1)
    <div class="mt-6">
        <nav class="flex flex-wrap gap-2 bg-white rounded-full border border-gray-200 p-2 shadow-sm">
        @foreach($licensees as $licensee)
            <button wire:click="setTab('{{$licensee->firstname}}')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === $licensee->firstname ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <p class="text-lg">{{ $licensee->firstname }}</p>
            </button>
        @endforeach    
        </nav>
    </div>
    @endif


@forelse($licensees as $licensee)
@if($activeTab==$licensee->firstname)
    <div class="p-5 rounded-2xl">
        <h2 class="text-gray-900 font-bold mb-4">
            {{ __('vespaket.presence_of' , ["name" => $licensee->firstname]) }}
        </h2>

        <x-cards-scroll nextElementId="{{ $licensee->nextActivityId }}">
            @forelse($licensee->combined as $entry)
                @php($activity = $entry['activity'])
                @php($member = $entry['member'])
                @if($activity instanceof \App\Models\Event && (!$member->isotm()))
                <livewire:event.card 
                    :event="$activity" :member="$member"
                    :key="'event-'.$member->id.'-'.$activity->id"
                ></livewire:event.card>                
                @elseif($activity instanceof \App\Models\Game && (!$member->isotm() || $activity->isDomicile()))
                <livewire:game.card 
                    :game="$activity"
                    :member="$member"
                    :key="'game-'.$member->id.'-'.$activity->id"
                ></livewire:game.card>                
                @endif 
            @empty
                <p class="text-gray-500">{{ __('vespaket.no_match') }}</p>
            @endforelse
        </x-cards-scroll>
    </div>
@endif
@empty
        <p class="text-gray-500">{{ __('vespaket.no_member') }}</p>
@endforelse
</div>