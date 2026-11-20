<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;
use App\Models\GameOption;
use App\Models\Member;

class Stats extends Component
{
    public Team $team;
    public $members = [];
    public $options = [];
    public array $statistics = [];

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->loadData();
    }

    private function loadData()
    {
        $this->options = GameOption::where("team_id",$this->team->id)
                        ->where('name', '!=', 'Numéro')
                        ->orderBy('order')
                        ->get();

        $gameIds = $this->team->games()
            ->orderBy('date')
            ->pluck('id');
        $gameOrder = $gameIds->flip();

        $this->members = $this->team->members()
            ->whereIn("type", [Member::TYPE_PLAYER,Member::TYPE_SUB])
            ->with('licensee')
            ->with(['gameOptions' => function ($query) use ($gameIds) {
                $query->whereIn('game_id', $gameIds)
                    ->whereIn('game_option_id', $this->options->pluck('id'));
            }])
            ->get()
            ->sortBy([["type"],["licensee.firstname"]]);

        $this->statistics = [];
        foreach ($this->members as $member) {
            foreach ($this->options as $option) {
                $values = $member->gameOptions->where('game_option_id', $option->id);

                if ($option->type === GameOption::TYPE_NUM) {
                    $this->statistics[$member->id][$option->id] = $values->sum(
                        fn ($gameOption) => is_numeric($gameOption->value) ? (float) $gameOption->value : 0
                    );
                    continue;
                }

                if ($option->type === GameOption::TYPE_CHECKBOX) {
                    $this->statistics[$member->id][$option->id] = $values->filter(
                        fn ($gameOption) => in_array(
                            strtolower((string) $gameOption->value),
                            ['true', 'yes', '1'],
                            true
                        )
                    )->count();
                    continue;
                }
                if ($option->type === GameOption::TYPE_OPPOSITION) {
                    $this->statistics[$member->id][$option->id] = 
                        "A:"
                        .$values->filter(
                            fn ($gameOption) => in_array(
                                strtolower((string) $gameOption->value),
                                ['A', 'a'],
                                true
                            )
                        )->count()
                        ." B:"
                        .$values->filter(
                            fn ($gameOption) => in_array(
                                strtolower((string) $gameOption->value),
                                ['B', 'b'],
                                true
                            )
                        )->count();
                        continue;
                }
                $this->statistics[$member->id][$option->id] = $values
                    ->sortBy(fn ($gameOption) => $gameOrder[$gameOption->game_id] ?? PHP_INT_MAX)
                    ->pluck('value')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->implode(', ');
            }
            $member->nbgames = $member->games()->wherePivot("selected",true)->count();
            $member->nbtrainings = $member->trainings()->wherePivot("present","yes")->count();
        }
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        return view('livewire.team.stats');
    }
}
