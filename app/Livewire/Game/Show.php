<?php

namespace App\Livewire\Game;

use Livewire\Component;

use App\Models\Game;
use App\Models\GameOption;
use App\Models\GameMemberOption;

class Show extends Component
{
    public Game $game;
    public $members;
    public $players;

    private $memberIds;

    public function mount()
    {
        $this->loadMembers();
    }

    public function loadMembers()
    {
        $this->memberIds = auth()->user()->members()->pluck('members.id');
        $this->players = $this->game->members()
                                ->where('type',\App\Models\Member::TYPE_PLAYER)
                                ->get();

        $this->members = $this->game->members()
            ->whereIn('members.id', $this->memberIds)
            ->get();
    }


    public function setAvailability($memberId, $value)
    {
        /* Protection uniquement les membres de l'utilisateur peuvent être modifiés */
        if ($this->members->contains('id',$memberId) ) {
            $this->game->members()->updateExistingPivot($memberId, [
                'availability' => $value
            ]);
        }
        $this->loadMembers();

    }

    public function setGameOption($memberId, $optionId, $value)
    {
        if($this->members->contains('id',$memberId)) {

            GameMemberOption::updateOrCreate(
                [
                    'game_id' => $this->game->id,
                    'member_id' => $memberId,
                    'game_option_id' => $optionId,
                ],
                [
                    'value' => $value,
                ]
            );
        }
        $this->loadMembers();
    }


    public function render()
    {
        $options = GameOption::where("team_id",$this->game->team_id)
                ->whereIn("display", [GameOption::DISP_ALL,GameOption::DISP_ALL_EDITABLE])
                ->orderBy('order')
                ->get();

        return view('livewire.game.show', [
            'options' => $options,
        ])->layout('layouts.app');
    }
}
