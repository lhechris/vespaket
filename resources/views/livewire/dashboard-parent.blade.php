<div class="space-y-6">
    <livewire:news.show />
    <livewire:InstallPrompt />
    <x-button
            id="subscribe-push-button"
         x-show="!{{$hasSubscription ? 'true' : 'false'}}"
         onclick="subscribeToPush()" type="button"> S'abonner</x-button>
        <p id="push-subscription-status" class="mt-2 text-sm" role="status" aria-live="polite"></p>
    <!-- MEMBRES -->
    @if(count($members)>1)
    <div class="mt-6">
        <nav class="flex flex-wrap gap-2 bg-white rounded-full border border-gray-200 p-2 shadow-sm">
        @foreach($members as $member)
            <button wire:click="setTab('{{$member->prenom}}')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === $member->prenom ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <p class="text-lg">{{ $member->prenom }}</p>
            </button>
        @endforeach    
        </nav>
    </div>
    @endif


@forelse($members as $member)
@if($activeTab==$member->prenom)
    <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800">
        <h2 class="text-white font-bold mb-4">
            {{ __('vespaket.presence_of' , ["name" => $member->prenom]) }}
        </h2>

        <x-cards-scroll nextElementId="game-{{$member->id}}-{{$member->nextGameId}}" >

            @forelse($member->combined as $game)
                @if($game instanceof \App\Models\Event)
                <livewire:event.card 
                    :event="$game" :member="$member"
                    :key="'event-'.$member->id.'-'.$game->id"
                ></livewire:event.card>                
                @elseif($game instanceof \App\Models\Game)
                <livewire:game.card 
                    :game="$game" 
                    :member="$member"
                    :key="'event-'.$member->id.'-'.$game->id"
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