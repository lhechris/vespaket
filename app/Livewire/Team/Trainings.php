<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;

class Trainings extends Component
{
    public Team $team;
    public $trainings;
    public $nextTrainingId;

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);
        
        $now = \Carbon\Carbon::now();
        $this->nextTrainingId = null;
        foreach ($this->team->trainings as $training) {
            if ($training->date >= $now) {
                $this->nextTrainingId = $training->id;
                break;
            }
        }
        $this->trainings = $this->team->trainings()
            ->withCount(['members as members_count' => function ($query) {
                    $query->where('member_training.present', 'yes');
            }])
            ->orderBy('date')
            ->get();        
    }

    public function render()
    {
        return view('livewire.team.trainings');
    }
}
