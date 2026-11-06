<?php

use App\Livewire\Actions\Logout;
use App\Models\Team;
use Livewire\Volt\Component;


new class extends Component
{
    public $teams;
    public $championships;

    public function mount() {
        if (auth()->user()) {
            if (auth()->user()->isAdmin()) {
                $this->teams = Team::all();

            } else if (auth()->user()->isCoach()) {
                $this->teams = auth()->user()->ownedTeams()->get();
            }
            $allteams = Team::select('id','name')->orderBy('name')->get();

            //Tri les équipes pour mettre les U avant les SF,SG
            $this->championships = $allteams->sortBy(function ($c) {
                    if (! preg_match('/^([A-Z])(\d*)([A-Z])(\d+)$/', $c->name, $m)) {
                        return [2, 0, '', 0, $c->name];
                    }

                    return [
                        $m[1] === 'U' ? 0 : 1,   // U avant S
                        (int) $m[2],             // 11 avant 13 avant 15...
                        $m[3],                   // F avant M (ordre alphabétique)
                        (int) $m[4],             // 1 avant 2
                        $c->name,
                    ];
                })
                ->values();
            }
    }
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-gray-900">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="hidden shrink-0 md:flex md:items-left">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />                        
                    </a>
                </div>
                @auth
                <!-- Navigation Links -->
                <div class="space-x-4 -my-px ms-0 sm:ms-10 flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate class="flex flex-col mt-1 items-center gap-1">
                        <img src="{{ asset('images/home.png') }}" alt="{{ __('actions.home') }}" class="h-6 w-6" />
                        <span class="text-sm">{{ __('actions.home') }}</span>
                    </x-nav-link>
                    <div class="px-1 pt-1 my-1">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="flex flex-col items-center gap-1 border border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <img src="{{ asset('images/club-de-basket-ball.png') }}" alt="{{ __('vespaket.club') }}" class="h-6 w-6" />
                                    <div class="flex">
                                        <span class="text-sm">{{ __('vespaket.club') }}</span>                               
                                        <span class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </button>
                            </x-slot>
                    
                            <x-slot name="content">
                            @foreach( $championships as $team)
                                <x-dropdown-link :href="route('championship.show', ['team'=>$team->id])" wire:navigate>
                                    {{ $team->name }}
                                </x-dropdown-link>
                            @endforeach
                            </x-slot>
                        </x-dropdown>
                    </div>
                    @if(auth()->user()->isCoach())

                    <div class="px-1 pt-1 my-1">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex flex-col items-center gap-1 border border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <img src="{{ asset('images/utilisateur.png') }}" alt="{{ __('vespaket.teams') }}" class="h-6 w-6" />
                                    <div class="flex">
                                        <span class="text-sm">{{ __('vespaket.teams') }}</span>                               
                                        <span class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </button>
                            </x-slot>
                    
                            <x-slot name="content">
                            @foreach( $teams as $team)
                                <x-dropdown-link :href="route('team.show', ['team'=>$team->id])" wire:navigate>
                                    {{ $team->name }}
                                </x-dropdown-link>
                            @endforeach
                                <x-dropdown-link :href="route('teams.create')" wire:navigate>
                                    {{ __('vespaket.create_team') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                    <div class="px-1 pt-1 my-1">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex flex-col items-center gap-1 border border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <img src="{{ asset('images/parametres.png') }}" alt="{{ __('actions.settings') }}" class="h-6 w-6" />
                                    <div class="flex">
                                        <span class="text-sm">{{ __('actions.settings') }}</span>                               
                                        <span class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                </button>
                            </x-slot>
                    
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile')" wire:navigate>
                                    {{ __('vespaket.profil') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('news')" wire:navigate>
                                    {{ __('vespaket.news') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('users')" wire:navigate>
                                    {{ __('vespaket.users') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('members')" wire:navigate>
                                    {{ __('vespaket.members') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('gymnase.show')" wire:navigate>
                                   {{ __('vespaket.sport_hall') }}
                                </x-dropdown-link>
                                <x-dropdown-link  href="" wire:navigate wire:click="logout">
                                   {{ __('actions.log_out') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @else
                    <x-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate class="flex flex-col mt-1 items-center gap-1">
                        <img src="{{ asset('images/utilisateur.png') }}" alt="{{ __('vespaket.profil') }}" class="h-6 w-6" />
                        <span class="text-sm">{{ __('vespaket.profil') }}</span>
                    </x-nav-link>                
                    <button wire:click="logout" class="w-full text-start flex mt-1 flex-col items-center gap-1" >
                        <x-dropdown-link>
                            <img src="{{ asset('images/logout.png') }}" alt="{{ __('actions.log_out') }}" class="h-6 w-6" />
                            <span class="text-sm truncate">{{ __('actions.log_out') }}</span>                                
                        </x-dropdown-link>
                    </button>
                @endif
                </div>
                @endauth
            </div>
        </div>
    </div>

</nav>
