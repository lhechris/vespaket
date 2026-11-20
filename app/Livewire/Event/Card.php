<?php

namespace App\Livewire\Event;

use Livewire\Component;
use App\Models\Event;
use App\Models\Member;

class Card extends Component
{
    public Event $event;
    public Member $member;
    public $availability;

    public function mount()
    {
        // Charge la valeur initiale du pivot
        $this->availability = $this->event->members()->where('member_id', $this->member->id)->first()?->pivot?->availability;
    }

    public function setAvailability($value)
    {

        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless($this->event->members()->whereKey($this->member->id)->exists(), 403);
        abort_unless(
            $user->isCoach()
                || ($user->isParent() && $user->licensees()
                    ->whereHas('members', fn ($query) => $query->whereKey($this->member->id))
                    ->exists()),
            403
        );

        validator(['value' => $value], [
            'value' => ['required', 'in:yes,no,maybe'],
        ])->validate();

        $this->event->members()->updateExistingPivot($this->member->id, [
            'availability' => $value,
        ]);

        $this->availability = $value;
    }

    public function render()
    {
        return view('livewire.event.card');
    }
}