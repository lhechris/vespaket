<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\GameOption;
use App\Models\Team;
use Illuminate\Validation\Rule;

class Parameters extends Component
{
    public Team $team;
    public array $gameoptions = [];

    public array $typeOptions = [];
    public array $displayOptions = [];

    public $teamName;
    public $teamWhatsapp;
    public $teamMsgConvocation;
    

    public function mount(): void
    {
        $this->authorize('TeamAccess', $this->team);
        $this->typeOptions = GameOption::types();
        $this->displayOptions = GameOption::displays();
        $this->loadData();        
    }

    private function loadData(): void
    {
        $this->gameoptions = $this->team->game_options()
            ->orderBy('order')
            ->get()
            ->keyBy('id')
            ->map(fn ($option) => [
                'id'      => $option->id,
                'name'    => $option->name,
                'type'    => $option->type,
                'order'   => $option->order,
                'display' => $option->display,
            ])
            ->toArray();

        $this->teamName = $this->team->name===null?'':$this->team->name;
        $this->teamWhatsapp = $this->team->whatsapp;
        $this->teamMsgConvocation = $this->team->msg_convocation;
    }

    public function addRow(): void
    {
        $tempKey = 'new-' . uniqid();

        $this->gameoptions[$tempKey] = [
            'id'      => null,
            'name'    => '',
            'type'    => '',
            'order'   => 0,
            'display' => '',
        ];
    }

    public function delete($key): void
    {
        $this->authorize('TeamAccess', $this->team);

        $data = $this->gameoptions[$key] ?? null;

        if ($data && ! empty($data['id'])) {
            GameOption::findOrFail($data['id'])->delete();
        }

        unset($this->gameoptions[$key]);
    }

    public function deleteTeam() {
        $this->authorize('TeamAccess', $this->team);

        $this->team->delete();
        redirect(route('dashboard'));
    }
    
    public function saveAll(): void
    {
        $this->authorize('TeamAccess', $this->team);

        $validated = validator($this->gameoptions, [
            '*.name'    => 'required|string|max:255',
            '*.type'    =>  ['required', Rule::in(array_keys(GameOption::types()))],
            '*.order'   => 'nullable|integer',
            '*.display' => ['nullable', Rule::in(array_keys(GameOption::displays()))],
        ])->validate();

        $this->team->update([
            'name' => $this->teamName,
            'whatsapp' => $this->teamWhatsapp,
            'msg_convocation' => $this->teamMsgConvocation
        ]);


        foreach ($this->gameoptions as $key => $data) {
            $row = $validated[$key];

            if (empty($data['id'])) {
                $option = $this->team->game_options()->create($row);

                unset($this->gameoptions[$key]);
                $this->gameoptions[$option->id] = array_merge($row, ['id' => $option->id]);
            } else {
                GameOption::findOrFail($data['id'])->update($row);
            }
        }

        $this->dispatch('saved');
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        
        return view('livewire.team.parameters');
    }
}