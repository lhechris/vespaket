<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Licensee;
use App\Models\Member;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

class ManageMembers extends Component
{
    public Team $team;

    public $search = '';
    public $results = [];

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);
    }

    public function updatedSearch()
    {
        $search = trim($this->search);

        $this->results = Licensee::query()
            ->with('members.team')
            ->where(function ($query) use ($search): void {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('licence', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();
    }

    public function addLicenseeMember(int $licenseeId, string $role): void
    {
        $this->authorize('TeamAccess', $this->team);

        if (! in_array($role, [Member::TYPE_PLAYER, Member::TYPE_COACH, Member::TYPE_OTM, Member::TYPE_SUB], true)) {
            throw ValidationException::withMessages([
                'role' => 'Le rôle sélectionné est invalide.',
            ]);
        }

        $licensee = Licensee::findOrFail($licenseeId);
        $member = $licensee->members()
                        ->where('team_id', $this->team->id)
                        ->where('type', $role)
                        ->first();

        if (! $member) {
            $member = $licensee->members()->create([
                'numero' => '',
                'type' => $role,
                'team_id' => $this->team->id
            ]);

            $this->addMember($member->id);
        }
        
        $this->updatedSearch();
    }

    private function addMember($memberId)
    {
        $this->authorize('TeamAccess', $this->team);

        $now = \Carbon\Carbon::now();

        foreach ($this->team->games()->get() as $game) {
            if ($now->lt(\Carbon\Carbon::parse($game->date))) {
                $alreadyAttached = $game->members()->whereKey($memberId)->exists();

                if (! $alreadyAttached) {
                    $game->members()->attach($memberId);
                }
            }
        }

        foreach ($this->team->events()->get() as $event) {
            if ($now->lt(\Carbon\Carbon::parse($event->date))) {
                $alreadyAttached = $event->members()->whereKey($memberId)->exists();

                if (! $alreadyAttached) {
                    $event->members()->attach($memberId);
                }
            }
        }
    }

    public function removeMember($memberId)
    {
        $this->authorize('TeamAccess', $this->team);

        $member = Member::find($memberId);

        $now = \Carbon\Carbon::now();

        //Detach des games uniquement pour les future, on garde les anciens
        foreach($this->team->games()->get() as $game) {
            if ($now->lt(\Carbon\Carbon::parse($game->date))) {
                \Log::info("detach $memberId dans le game $game->id" );
                $game->members()->detach($memberId);
            }
        }        
        //Detach des events uniquement pour les future, on garde les anciens
        foreach($this->team->events()->get() as $event) {
            if ($now->lt(\Carbon\Carbon::parse($event->date))) {
                \Log::info("detach $memberId dans l'event $event->id" );
                $event->members()->detach($memberId);
            }
        }        

        $member->delete();

    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        
        return view('livewire.team.manage-members', [
            'members' => $this->team->members()->with('licensee')->get()->sortby([["type"],["licensee.firstname"]]),
        ])->layout('layouts.app');
    }
}