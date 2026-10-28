<?php

namespace App\Livewire\Member;

use Livewire\Component;
use App\Models\Member;

class Manage extends Component
{
    public array $members = [];
    public array $newMember = [
        'name' => '',
        'prenom' => '',
        'type' => 'player',
        'licence' => '',
        'numero' => '',
    ];
    public bool $creating = false;
    public ?int $editingIndex = null;
    public string $memberSearch = '';
    public int $membersPage = 1;
    public int $membersLastPage = 1;

    public function mount()
    {
        $this->loadMembers();
    }

    public function loadMembers()
    {
        $memberQuery = Member::query()
            ->orderBy('prenom')
            ->orderBy('name');


        $search = trim($this->memberSearch);
        if ($search !== '') {
            $memberQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        $this->membersLastPage = max(1, (int) ceil($memberQuery->count() / 10));
        $this->membersPage = min($this->membersPage, $this->membersLastPage);

        $this->members = $memberQuery
            ->forPage($this->membersPage, 10)
            ->get()
            ->map(fn (Member $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'prenom' => $member->prenom,
                'type' => $member->type,
                'licence' => $member->licence,
                'numero' => $member->numero,
            ])
            ->all();
    }

    public function updatedMemberSearch(): void
    {
       // $this->membersPage = 1;
        $this->editingIndex = null;
        $this->loadMembers();
    }

    public function previousMembersPage(): void
    {
        if ($this->membersPage > 1) {
            $this->membersPage--;
            $this->editingIndex = null;
            $this->loadMembers();
        }
    }

    public function nextMembersPage(): void
    {
        if ($this->membersPage < $this->membersLastPage) {
            $this->membersPage++;
            $this->editingIndex = null;
            $this->loadMembers();
        }
    }

    public function startCreate(): void
    {
        $this->resetValidation();
        $this->editingIndex = null;
        $this->creating = true;
    }

    public function cancelCreate(): void
    {
        $this->resetValidation();
        $this->newMember = [
            'name' => '',
            'prenom' => '',
            'type' => 'player',
            'licence' => '',
            'numero' => '',
        ];
        $this->creating = false;
    }

    public function createMember(): void
    {
        $this->validate([
            'newMember.name' => ['required', 'string', 'min:2'],
            'newMember.prenom' => ['nullable', 'string'],
            'newMember.type' => ['required', 'in:player,coach,staff'],
            'newMember.licence' => ['nullable', 'string'],
            'newMember.numero' => ['nullable', 'integer'],
        ]);

        Member::create($this->newMember);
        $this->loadMembers();
        $this->cancelCreate();
        session()->flash('success', 'Membre créé.');
    }

    public function saveMember(int $index): void
    {
        $this->validate([
            "members.$index.name" => ['required', 'string', 'min:2'],
            "members.$index.prenom" => ['nullable', 'string'],
            "members.$index.type" => ['required', 'in:player,coach,staff'],
            "members.$index.licence" => ['nullable', 'string'],
            "members.$index.numero" => ['nullable', 'string'],
        ]);

        $member = $this->members[$index];
        Member::findOrFail($member['id'])->update([
            'name' => $member['name'],
            'prenom' => $member['prenom'],
            'type' => $member['type'],
            'licence' => $member['licence'],
            'numero' => $member['numero'],
        ]);

        session()->flash('success', 'Membre modifié.');
        $this->editingIndex = null;
    }

    public function editMember(int $index): void
    {
        $this->creating = false;
        $this->editingIndex = $index;
    }

    public function cancelEdit(): void
    {
        $this->loadMembers();
        $this->editingIndex = null;
    }

    public function delete($id)
    {
        Member::findOrFail($id)->delete();
        $this->loadMembers();
        $this->editingIndex = null;
    }

    public function render()
    {
        return view('livewire.member.manage')
            ->layout('layouts.app');
    }
}