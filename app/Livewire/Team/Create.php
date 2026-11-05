<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;

class Create extends Component
{
    public $name;
    public $whatsapp;
    public $msg_convocation="Bonjour pour le match du %JOURMATCH% voici la liste des joueurs sélectionnées %SELECTION%\n\nEt non sélectionnées %NONSELECTION%\n\nLe rendez-vous est %RENDEZVOUS%";

    public function create()
    {
        $this->validate([
            'name' => 'required|min:3'
        ]);


        $this->authorize('TeamCreate',Team::class);

        $team = Team::create([
            'name' => $this->name,
            'whatsapp' => $this->whatsapp,
            'msg_convocation' => $this->msg_convocation,
            'owner_id' => auth()->id()
        ]);

        $team->owners()->attach(auth()->id());

        // récupérer le member du coach
        //$member = auth()->user()->members()->first();

        //$team->members()->attach($member->id);

        session()->flash('success', 'Équipe créée');

        $this->reset('name');
    }

    public function render()
    {
        return view('livewire.team.create')
            ->layout('layouts.app');
    }
}
