<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Game;
use App\Models\Team;
use App\Models\Member;

use Carbon\Carbon;

class Dashboard extends Component
{
    public $licensees;
    public $teams;
    public $hasSubscription;
    public $activeTab;

    public function mount()
    {
        $this->activeTab = '';
        $this->loaddata();
    }

    public function hydrate(): void
    {
        $this->loadLicensees();
    }

    private function loaddata()
    {
        if (auth()->user()) {
            if (auth()->user()->isAdmin()) {
                $this->teams = Team::all();

            } else if (auth()->user()->isCoach()) {
                $this->teams = auth()->user()->ownedTeams()->get();
            }
            
            $this->loadLicensees();

            if (count($this->licensees)>0) {
                $this->activeTab = $this->licensees[0]->firstname;
            }
        }
        $this->checkPendingGames();

        $this->hasSubscription = auth()->user()->pushSubscriptions()->exists();

    }

    private function loadLicensees(): void
    {
        $this->licensees = auth()->user()->licensees()
            ->with([
                'members' => fn ($query) => $query
                    ->whereHas('games', fn ($games) => $games->where('game_member.called', true))
                    ->with([
                        'games' => fn ($games) => $games->where('game_member.called', true),
                        'events'=> fn ($games) => $games->where('event_member.called', true),
                    ]),
            ])
            ->get();
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

        $licenseeIds = $user->licensees()->pluck('licensees.id');
        $memberIds = Member::query()
            ->whereIn('licensee_id', $licenseeIds)
            ->pluck('members.id');

        $games = \App\Models\Game::whereHas('members', function ($query) use ($memberIds) {
            $query->whereIn('members.id', $memberIds)
                ->whereNull('availability')
                ->where('called',true);
        })
        ->whereBetween('date', [now(), now()->addDays(5)])
        ->get();

        if ($games->isNotEmpty()) {

            if (session()->has('notified')) return;
            session()->put('notified', true);

            $game = $games->first();

            $this->dispatch('notify', [
                'title' => '🏀 Match bientôt',
                'body' => 'Merci de renseigner les disponibilités pour le match '
                            .$game->team->name.
                            ' de '.Carbon::parse($game->date)->translatedFormat('l d F'),
            ]);
            
        }
    }

    public function installPwa() {
        $this->dispatch('triggerInstall');
    }

    public function render()
    {
        foreach ($this->licensees as $licensee) {
            $combined = collect();

            foreach ($licensee->members as $member) {
                foreach ($member->games as $game) {
                    $combined->push(['activity' => $game, 'member' => $member]);
                }

                foreach ($member->events as $event) {
                    $combined->push(['activity' => $event, 'member' => $member]);
                }
            }

            $licensee->combined = $combined
                ->sortBy(fn (array $entry) => Carbon::parse($entry['activity']->date)->getTimestamp())
                ->values();

            $nextActivity = $licensee->combined->first(
                fn (array $entry) => Carbon::parse($entry['activity']->date)->greaterThanOrEqualTo(now())
            );
            $licensee->nextActivityId = $nextActivity
                ? ($nextActivity['activity'] instanceof Game ? 'game' : 'event')
                    .'-'.$nextActivity['member']->id
                    .'-'.$nextActivity['activity']->id
                : null;
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
