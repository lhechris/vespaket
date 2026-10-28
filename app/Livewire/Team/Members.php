<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;

class Members extends Component
{
    public Team $team;
    public $members;
    public $coaches;
    public $owners;
    public ?int $editingMemberId = null;
    public array $editingMember = [
        'prenom' => '',
        'licence' => '',
        'numero' => '',
    ];

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->loadMembers();

        $this->coaches = $this->team->coaches()->get();
        $this->owners = $this->team->owners()->get();
    }

    private function loadMembers(): void
    {
        $this->members = $this->team->members()
                    ->withCount(['games as games_count' => function ($query) {
                        $query->where('game_member.selected', 1);
                    }])
                    ->withCount(['trainings as trainings_count' => function ($query) {
                        $query->where('member_training.present', 'yes');
                    }])
                    ->where('type',\App\Models\Member::TYPE_PLAYER)
                    ->get();
    }

    public function editMember(int $memberId): void
    {
        $member = $this->team->members()->whereKey($memberId)->firstOrFail();

        $this->editingMemberId = $member->id;
        $this->editingMember = [
            'prenom' => $member->prenom ?? '',
            'licence' => $member->licence ?? '',
            'numero' => $member->numero ?? '',
        ];
        $this->resetValidation();
    }

    public function saveMember(): void
    {
        $this->authorize('TeamAccess', $this->team);

        $this->validate([
            'editingMember.prenom' => ['nullable', 'string'],
            'editingMember.licence' => ['nullable', 'string'],
            'editingMember.numero' => ['nullable', 'string'],
        ]);

        $member = $this->team->members()->whereKey($this->editingMemberId)->firstOrFail();
        $member->update($this->editingMember);

        $this->editingMemberId = null;
        $this->loadMembers();
    }

    public function cancelEdit(): void
    {        
        $this->editingMemberId = null;
        $this->resetValidation();

    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        
        $this->loadMembers();

        return view('livewire.team.members');
    }
}
