<?php

namespace App\Livewire\Game;

use App\Models\Game;
use App\Models\GameMemberOption;
use App\Models\GameOption;
use App\Models\Member;
use Livewire\Component;

class Show extends Component
{
    public Game $game;

    public $members;

    public $players;

    public function mount(): void
    {
        $this->loadMembers();
    }

    public function loadMembers(): void
    {
        $memberIds = auth()->user()->licensees()
            ->with('members')
            ->get()
            ->flatMap(fn ($licensee) => $licensee->members->modelKeys())
            ->unique()
            ->values();

        $this->players = $this->game->members()
            ->whereIn('type', [Member::TYPE_PLAYER, Member::TYPE_SUB])
            ->where('game_member.called', true)
            ->with([
                'licensee',
                'gameOptions' => fn ($query) => $query->where('game_id', $this->game->id),
            ])
            ->get();

        $this->members = $this->game->members()
            ->where('game_member.called', true)
            ->whereIn('members.id', $memberIds)
            ->with('licensee')
            ->get();
    }

    public function setAvailability(int $memberId, string $value): void
    {
        $this->authorizeOwnGameMember($memberId);

        validator(['value' => $value], [
            'value' => ['required', 'in:yes,no,maybe'],
        ])->validate();

        $this->game->members()->updateExistingPivot($memberId, [
            'availability' => $value,
        ]);
        $this->loadMembers();
    }

    public function setGameOption(int $memberId, int $optionId, ?string $value): void
    {
        $this->authorizeOwnGameMember($memberId);
        abort_unless(GameOption::query()
            ->whereKey($optionId)
            ->where('team_id', $this->game->team_id)
            ->whereIn('display', [GameOption::DISP_ALL_EDITABLE, GameOption::DISP_COACH])
            ->exists(), 403);

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
        $this->loadMembers();
    }

    private function authorizeOwnGameMember(int $memberId): void
    {
        abort_unless(auth()->user()?->licensees()
            ->whereHas('members', fn ($query) => $query->whereKey($memberId))
            ->exists(), 403);
        abort_unless($this->game->members()->whereKey($memberId)->exists(), 403);
    }

    public function render()
    {
        $options = GameOption::where('team_id', $this->game->team_id)
            ->whereIn('display', [GameOption::DISP_ALL, GameOption::DISP_ALL_EDITABLE])
            ->orderBy('order')
            ->get();

        return view('livewire.game.show', [
            'options' => $options,
        ])->layout('layouts.app');
    }
}
