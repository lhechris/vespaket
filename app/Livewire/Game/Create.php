<?php

namespace App\Livewire\Game;

use Livewire\Component;
use App\Models\Game;
use App\Models\Team;
use App\Models\Place;

class Create extends Component
{
    public Team $team;

    public $date;
    public $titre;
    public $location;
    public $rendezvous;
    public $score;
    public $commentaire;
    public $place_id;
    public $numero;

    public $places;

    public function mount() 
    {
        $this->places=Place::orderby('name')->get();
    }

    public function save()
    {
        $this->validate([
            'date' => "required",
            'titre' => 'required',
            'rendezvous' => 'required']);

        $game = Game::create([
            'team_id' => $this->team->id,
            'place_id' => $this->place_id,
            'date' => $this->date,
            'location' => $this->location,
            'titre' => $this->titre,
            'rendezvous' => $this->rendezvous,
            'score' => $this->score,
            'commentaire' => $this->commentaire,
            'numero' => $this->numero
        ]);

        // initialiser tous les joueurs
        foreach ($this->team->players as $player) {
            $game->members()->attach($player->id);
        }
        foreach ($this->team->otms as $otm) {
            $game->members()->attach($otm->id);
        }
        foreach ($this->team->subs as $player) {
            $game->members()->attach($player->id,["called"=>false]);
        }
        return redirect(route('game.edit',[$game->id]));
    }

    public function render()
    {
        return view('livewire.game.create')
            ->layout('layouts.app');
    }
}