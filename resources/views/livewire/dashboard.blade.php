<div class="space-y-6">
    <!-- HEADER -->
    <div class="flex items-center justify-between">

        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ auth()->user()->firstname }}
                </h1>
                <p class="text-gray-400 text-sm">
                    AS Labarthaise Basket
                </p>
            </div>
            <livewire:InstallPrompt />
            <x-button 
                id="subscribe-push-button"
                x-show="!{{$hasSubscription ? 'true' : 'false'}}"
                onclick="subscribeToPush()" type="button"> S'abonner</x-button>
            <p id="push-subscription-status" class="mt-2 text-sm" role="status" aria-live="polite"></p>
        </div>
 

    </div>

    <livewire:news.show />

    <!-- ACTIONS -->
    <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800">

        <h2 class="text-white font-bold mb-4">
            ⚡ {{ __('vespaket.quick_actions') }}
        </h2>

        <div class="flex flex-wrap gap-3">

            <a href="{{ route('teams.create') }}"
               class="bg-yellow-400 text-black px-4 py-2 rounded-lg font-semibold hover:bg-yellow-300">
                ➕ {{ __('vespaket.create_team') }}
            </a>

            <a href="{{ route('members') }}"
               class="bg-white text-black px-4 py-2 rounded-lg font-semibold hover:bg-gray-200">
                👥 {{ __('vespaket.manage_members') }}
            </a>

            <a href="{{ route('gymnase.show') }}"
               class="bg-white text-black px-4 py-2 rounded-lg font-semibold hover:bg-gray-200">
                ​📍​ {{ __('vespaket.manage_sport_hall') }}
            </a>
        </div>

    </div>

    <!-- TEAMS -->
    <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800">

        <h2 class="text-white font-bold mb-4">
            🏀 {{ __('vespaket.my_teams') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            @forelse($teams as $team)

                <a href="{{ route('team.show', ['team' => $team->id]) }}" >
                    <div class="flex justify-between items-center bg-black p-4 rounded-xl border border-yellow-300 hover:scale-[1.02] transition">

                        <div>
                            <p class="text-white font-semibold">
                                {{ $team->name }}
                            </p>

                            <p class="text-gray-400 text-sm">
                                {{ $team->members()->count() }} {{ __('vespaket.members') }}
                            </p>
                        </div>

                        <span class="bg-yellow-400 text-black px-4 py-2 rounded-lg font-semibold hover:bg-yellow-300">
                            {{ __('actions.show') }}
                        </span>

                    </div>
                </a>

            @empty
                <p class="text-gray-500">{{ __('vespaket.no_teams') }}</p>
            @endforelse

        </div>
    </div>

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

    
    <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800">
@forelse($members as $member)
@if($activeTab==$member->prenom)

        <h2 class="text-white font-bold mb-4">
            {{ __('vespaket.presence_of', ["name" => $member->prenom])  }}
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
@endif
@empty
        <p class="text-gray-500">{{ __('vespaket.no_member') }}</p>
@endforelse

    </div>

</div>