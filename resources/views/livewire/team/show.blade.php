<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex gap-4">

        <div>
            <div class="flex">
                <h1 class="text-3xl font-bold text-gray-900">
                    🏀 {{ $team->name }}
                </h1>
                <button wire:click="setTab('parameters')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'parameters' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                    <img src="{{ asset('images/parametres.png') }}" alt="{{ __('actions.settings') }}"/>
                    <p class="text-xs hidden sm:block">{{ __('actions.settings') }}</p></p><p class="text-xs sm:hidden">{{ __('actions.settings') }}</p>
                </button>
            </div>
            <div class="text-gray-600 flex gap-2" >
                <p>{{ $team->players()->count() }} {{ __('vespaket.players') }}</p>
                <p>{{ $team->staffs()->count() }} {{ __('vespaket.staffs') }}</p>
                <p>{{ $team->coaches()->count() }} {{ __('vespaket.coaches') }}</p>
                <p>{{ $team->games()->count() }} {{ __('vespaket.matchs') }}</p>
                <p>{{ $team->trainings()->count() }} {{ __('vespaket.trainings') }}</p>
            </div>
        </div>

    </div>

    <div class="mt-6">
        <nav class="flex flex-wrap gap-2 bg-white rounded-full border border-gray-200 p-2 shadow-sm">
            <button wire:click="setTab('games')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'games' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <img src="{{ asset('images/basketball.png') }}" alt="{{ __('vespaket.matchs') }}"/>
                <p class="text-xs">{{ __('vespaket.matchs') }}</p>
            </button>
            <button wire:click="setTab('members')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'members' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <img src="{{ asset('images/utilisateurs.png') }}" alt="{{ __('vespaket.headcount') }}"/>
                <p class="text-xs">{{ __('vespaket.headcount') }}</p>
            </button>
            <button wire:click="setTab('trainings')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'trainings' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <img src="{{ asset('images/exercice.png') }}" alt="{{ __('vespaket.trainings') }}"/>
                <p class="text-xs hidden sm:block">{{ __('vespaket.trainings') }}</p><p class="text-xs sm:hidden">{{ __('vespaket.training_cut') }}</p>
            </button>
            <button wire:click="setTab('selections')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'selections' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <img src="{{ asset('images/selection.png') }}" alt="{{ __('vespaket.selections') }}"/>
                <p class="text-xs">{{ __('vespaket.selections') }}</p>
            </button>
            <button wire:click="setTab('events')"
                    class="px-2 sm:px-4 py-2 rounded-full text-sm font-semibold {{ $activeTab === 'events' ? 'bg-gray-500 text-white' : 'bg-transparent text-gray-700 hover:bg-gray-100' }}">
                <img src="{{ asset('images/fete.png') }}" alt="{{ __('vespaket.events') }}"/>
                <p class="text-xs">{{ __('vespaket.events') }}</p>
            </button>
        </nav>
    </div>

    <!-- JOUEURS -->
    @if($activeTab === 'members')
        <livewire:team.members :team="$team" />
    @endif

    <!-- MATCHS -->
    @if($activeTab === 'games')
        <livewire:team.games :team="$team" />
    @endif

    <!-- ENTRAINEMENTS -->
    @if($activeTab === 'trainings')
        <livewire:team.trainings :team="$team" />
    @endif

    <!-- EVENEMENTS -->
    @if($activeTab === 'events')
        <livewire:team.events :team="$team" />
    @endif

    <!-- SELECTIONS -->
    @if($activeTab === 'selections')
        <livewire:team.selections :team="$team" />
    @endif

    <!-- PARAMETERS -->
    @if($activeTab === 'parameters')
        <livewire:team.parameters :team="$team" />
    @endif


</div>
