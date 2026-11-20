<?php

namespace App\Livewire\Event;

use Livewire\Component;
use App\Models\Event;

class Edit extends Component
{

    public Event $event;
    public bool $editingEvent = false;
    public string $eventTitle = '';
    public string $eventDate = '';
    public string $eventLocation = '';
    public string $eventDescription = '';

    public string $message = '';
    public $members;

    public function mount()
    {
        $this->eventTitle = $this->event->titre===null?'':$this->event->titre;
        $this->eventDate = $this->event->date;
        $this->eventLocation = $this->event->location;
        $this->eventDescription = $this->event->description ?? '';
        
        $this->loaddata();

    }

    private function loaddata() {
        $this->members = $this->event->members()
                            ->get()
                            ->sortBy([["type"],["licensee.firstname"]]);
    }




    public function toggleEditingEvent()
    {
        $this->editingEvent = !$this->editingEvent;
        if ($this->editingEvent) {
            $this->eventTitle = $this->event->titre===null?'':$this->event->titre;
            $this->eventDate = $this->event->date;
            $this->eventLocation = $this->event->location;
            $this->eventDescription = $this->event->description ?? '';           
        }
        $this->loaddata();
    }

    public function toggleCalled($memberId)
    {
        $pivot = $this->event->members()->find($memberId)->pivot;

        $this->event->members()->updateExistingPivot($memberId, [
            'called' => !$pivot->called
        ]);

        $this->loaddata();

    }

    public function updateEvent()
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        $this->event->update([
            'titre' => $this->eventTitle,
            'date' => $this->eventDate,
            'location' =>  $this->eventLocation,
            'description' => $this->eventDescription
        ]);
        $this->editingEvent = false;
        $this->loaddata();
    }

    public function setAvailability($memberId,$value)
    {
        $this->event->members()->updateExistingPivot($memberId, [
            'availability' => $value
        ]);
        $this->loaddata();
    }

    public function deleteEvent() {
        $team_id=$this->event->team_id;
        $this->event->delete();
        redirect(route('team.show',[$team_id]));
    }

    public function render()
    {
        return view('livewire.event.edit')
                ->layout('layouts.app');
    }
}
