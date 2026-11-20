<?php

namespace App\Livewire\Licensee;

use App\Models\Licensee;
use App\Models\Member;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    private const ROLES = [
        Member::TYPE_PLAYER => 'vespaket.player',
        Member::TYPE_COACH => 'vespaket.coach',
        Member::TYPE_OTM => 'vespaket.otm',
        Member::TYPE_SUB => 'vespaket.sub',
    ];

    public ?Licensee $licensee = null;

    public string $firstname = '';

    public string $name = '';

    public string $licence = '';

    public array $teamAssignments = [];

    public function mount(?Licensee $licensee = null): void
    {
        $this->licensee = $licensee;
        $this->firstname = $licensee?->firstname ?? '';
        $this->name = $licensee?->name ?? '';
        $this->licence = $licensee?->licence ?? '';
        $this->loadTeamAssignments();
    }

    private function availableTeams(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return Team::query()->whereRaw('1 = 0')->get();
        }

        if ($user->isAdmin()) {
            return Team::query()->orderBy('name')->get();
        }

        return $user->ownedTeams()->orderBy('name')->get();
    }

    private function loadTeamAssignments(): void
    {
        $availableTeamIds = $this->availableTeams()->modelKeys();
        $assignments = [];

        if ($this->licensee) {
            foreach ($this->licensee->members()->with('team')->get() as $member) {
                if (! array_key_exists($member->type, self::ROLES)) {
                    continue;
                }

                if ($member->team) {
                    if (! in_array($member->team->id, $availableTeamIds, true)) {
                        continue;
                    }

                    $assignments[$member->team->id] ??= [
                        'team_id' => (string) $member->team->id,
                        'roles' => array_fill_keys(array_keys(self::ROLES), false),
                    ];
                    $assignments[$member->team->id]['roles'][$member->type] = true;
                }
            }
        }

        $this->teamAssignments = array_values($assignments);
    }

    public function addTeamAssignment(): void
    {
        $this->teamAssignments[] = [
            'team_id' => '',
            'roles' => array_fill_keys(array_keys(self::ROLES), false),
        ];
    }

    public function removeTeamAssignment(int $index): void
    {
        if (! array_key_exists($index, $this->teamAssignments)) {
            return;
        }

        unset($this->teamAssignments[$index]);
        $this->teamAssignments = array_values($this->teamAssignments);
        $this->resetValidation();
    }

    public function save(): void
    {
        $teams = $this->availableTeams();
        $rules = [
            'firstname' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'licence' => ['nullable', 'string', 'max:255'],
            'teamAssignments' => ['array'],
            'teamAssignments.*' => ['array:team_id,roles'],
            'teamAssignments.*.team_id' => ['required', 'integer', Rule::in($teams->modelKeys()), 'distinct'],
            'teamAssignments.*.roles' => ['required', 'array:'.implode(',', array_keys(self::ROLES))],
            'teamAssignments.*.roles.*' => ['boolean'],
        ];
        $validated = $this->validate($rules);
        $validated['licence'] = $validated['licence'] !== '' ? $validated['licence'] : null;

        $desiredRoles = [];
        foreach ($validated['teamAssignments'] as $assignment) {
            $teamId = (int) $assignment['team_id'];
            foreach (array_keys(self::ROLES) as $role) {
                $desiredRoles[$teamId][$role] = in_array($assignment['roles'][$role] ?? false, [true, 1, '1'], true);
            }
        }

        unset($validated['teamAssignments']);
        $wasUpdate = $this->licensee !== null;

        DB::transaction(function () use ($validated, $teams, $desiredRoles): void {
            if ($this->licensee) {
                $this->licensee->update($validated);
            } else {
                $this->licensee = Licensee::create($validated);
            }

            $this->syncTeamAssignments($this->licensee, $teams, $desiredRoles);
        });

        session()->flash('success', $wasUpdate ? 'Licencié modifié.' : 'Licencié créé.');
        $this->dispatch('licensee-saved');

        $this->reset('firstname', 'name', 'licence');
        $this->licensee = null;
        $this->teamAssignments = [];
    }

    private function syncTeamAssignments(Licensee $licensee, Collection $teams, array $desiredRoles): void
    {
        $members = $licensee->members()->with('team')->get();
        $availableTeamIds = $teams->modelKeys();

        foreach ($members as $member) {
            if (! array_key_exists($member->type, self::ROLES)) {
                continue;
            }

            $teamId = (int) $member->team_id;
            if (
                $member->team_id !== null
                && in_array($teamId, $availableTeamIds, true)
                && ! ($desiredRoles[$teamId][$member->type] ?? false)
            ) {
                $this->detachMemberFromTeam($member, $member->team);
            }
        }

        foreach ($desiredRoles as $teamId => $roles) {
            $team = $teams->firstWhere('id', $teamId);
            if (! $team) {
                continue;
            }

            foreach ($roles as $role => $selected) {
                if (! $selected) {
                    continue;
                }

                $member = $members->first(fn (Member $candidate): bool => (
                    $candidate->type === $role
                    && (int) $candidate->team_id === (int) $teamId
                ));
                if (! $member) {
                    $member = $members->first(fn (Member $candidate): bool => (
                        $candidate->type === $role && $candidate->team_id === null
                    ));

                    if ($member) {
                        $member->update(['team_id' => $team->id]);
                    } else {
                        $member = $licensee->members()->create([
                            'numero' => '',
                            'type' => $role,
                            'team_id' => $team->id,
                        ]);
                        $members->push($member);
                    }

                    $this->attachMemberToTeam($member, $team);
                }
            }
        }
    }

    private function attachMemberToTeam(Member $member, Team $team): void
    {
        $member->update(['team_id' => $team->id]);
        $now = now();

        foreach ($team->games()->get() as $game) {
            if ($now->lt(Carbon::parse($game->date)) && ! $game->members()->whereKey($member->id)->exists()) {
                $game->members()->attach($member->id,["called"=>false]);
            }
        }

        foreach ($team->events()->get() as $event) {
            if ($now->lt(Carbon::parse($event->date)) && ! $event->members()->whereKey($member->id)->exists()) {
                $event->members()->attach($member->id,["called"=>false]);
            }
        }
    }

    private function detachMemberFromTeam(Member $member, Team $team): void
    {
        $now = now();

        foreach ($team->games()->get() as $game) {
            if ($now->lt(Carbon::parse($game->date))) {
                $game->members()->detach($member->id);
            }
        }

        foreach ($team->events()->get() as $event) {
            if ($now->lt(Carbon::parse($event->date))) {
                $event->members()->detach($member->id);
            }
        }
        $member->delete();
    }

    public function render(): View
    {
        return view('livewire.licensee.form', [
            'teams' => $this->availableTeams(),
            'roles' => self::ROLES,
        ]);
    }
}
