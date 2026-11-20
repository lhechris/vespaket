<?php

namespace App\Livewire\Event;

use Livewire\Component;
use App\Models\Event;
use App\Models\Member;

class Show extends Component
{
    public Event $event;
    public $members;
    public $menberIds;
    public $players;

    public function mount() {
        $this->loaddata();
    }

    public function setAvailability($memberId,$value)
    {
        abort_unless(auth()->user()?->licensees()
            ->whereHas('members', fn ($query) => $query->whereKey($memberId))
            ->exists(), 403);
        abort_unless($this->event->members()->whereKey($memberId)->exists(), 403);

        validator(['value' => $value], [
            'value' => ['required', 'in:yes,no,maybe'],
        ])->validate();

        $this->event->members()->updateExistingPivot($memberId, [
            'availability' => $value,
        ]);
        $this->loaddata();

    }
 
    private function loaddata() {
        $memberIds = auth()->user()->licensees()
            ->with('members')
            ->get()
            ->flatMap(fn ($licensee) => $licensee->members->modelKeys())
            ->unique()
            ->values();

        $this->players = $this->event->members()
            ->whereIn('type', [Member::TYPE_PLAYER, Member::TYPE_SUB])
            ->where('event_member.called', true)
            ->with([
                'licensee',                
            ])
            ->get();

        $this->members = $this->event->members()
            ->where('event_member.called', true)
            ->whereIn('members.id', $memberIds)
            ->with('licensee')
            ->get();
    }

    public function render()
    {
        return view('livewire.event.show')->layout('layouts.app');
    }
}
