<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Game;
use App\Models\Team;
use App\Models\Member;

use Carbon\Carbon;

class Dashboard extends Component
{
    public $members;
    public $teams;
    public $hasSubscription;
    public $activeTab;

    public function mount()
    {
        $this->activeTab = '';
        $this->loaddata();
    }

    private function loaddata()
    {
        if (auth()->user()) {
            if (auth()->user()->isAdmin()) {
                $this->teams = Team::all();

            } else if (auth()->user()->isCoach()) {
                $this->teams = auth()->user()->ownedTeams()->get();
            }
            
            $this->members = auth()->user()->members()
                ->with('games')
                ->with('events')
                ->where('type',Member::TYPE_PLAYER)
                ->get();
            if (count($this->members)>0) {
                $this->activeTab = $this->members[0]->prenom;
            }
         
        }
        $this->checkPendingGames();

        $this->hasSubscription = auth()->user()->pushSubscriptions()->exists();

    }
    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }


    /**
     * Verifie s'il y a des matchs qui n'ont pas été validée 
     * pour genérer une notification
     */
    public function checkPendingGames()
    {

        $user = auth()->user();

        $members = $user->members()->pluck('members.id');

        $games = \App\Models\Game::whereHas('members', function ($query) use ($members) {
            $query->whereIn('members.id', $members)
                ->whereNull('availability');
        })
        ->whereBetween('date', [now(), now()->addDays(5)])
        ->get();

        if ($games->isNotEmpty()) {

            $game = $games->first();

            $this->dispatch('notify', [
                'title' => '🏀 Match bientôt',
                'body' => 'Tu as des disponibilités à renseigner'
            ]);
            
            if (session()->has('notified')) return;
            session()->put('notified', true);

        }
    }

    public function installPwa() {
        $this->dispatch('triggerInstall');
    }

    public function render()
    {
        foreach($this->members as &$member) {
            $combined = $member->games->merge($member->events);
            $member->combined = $combined->sortBy('date');
        }

        //Recherche le prochain match à afficher
        $now = \Carbon\Carbon::now();
        foreach ($this->members as &$member) {
            $member->nextGameId = null;
            foreach ($member->combined as $game) {
                if ($game->date >= $now) {
                    $member->nextGameId = $game->id;
                    break;
                }
            }
        }

        if (auth()->user() && auth()->user()->isCoach()){
            return view('livewire.dashboard')
                ->layout('layouts.app');
        
        } else if (auth()->user() && (auth()->user()->isParent()||auth()->user()->isPlayer())){
            return view('livewire.dashboard-parent')
                ->layout('layouts.app');
        }
        else {
             abort(403);
        }
    }
}
