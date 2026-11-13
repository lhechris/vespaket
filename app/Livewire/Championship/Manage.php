<?php

namespace App\Livewire\Championship;

use App\Models\Championship;
use App\Models\Team;
use App\Services\DesimoneService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Manage extends Component
{
    private const DESIMONE_CLUB_ID = 12374;

    #[Locked]
    public Team $team;

    #[Locked]
    public ?Championship $championship = null;

    public string $name = '';
    public string $season = '';
    public string $category = '';
    public string $division = '';
    public string $pool = '';
    public string $source = 'desimone';
    public string $scorencoteamid = '';
    public string $desimoneCompetitionId = '';
    public string $desimonePouleId = '';
    public string $desimoneTeamId = '';
    public string $desimoneSelection = '';

    #[Locked]
    public array $desimoneTeams = [];

    public function mount(Team $team, ?Championship $championship = null): void
    {
        $this->team = $team;

        $this->authorize('TeamAccess', $this->team);

        if ($championship) {
            $championshipTeam = $championship->teams()
                ->where('team_id', $this->team->id)
                ->first();
            abort_unless($championshipTeam, 404);
            $this->authorize('update', $championship);

            $this->championship = $championship;
            $this->name = $championship->name;
            $this->season = $championship->season;
            $this->category = $championship->category ?? '';
            $this->division = $championship->division ?? '';
            $this->pool = $championship->pool ?? '';
            $this->source = in_array($championship->source, ['scorenco', 'desimone'], true)
                ? $championship->source
                : ($championshipTeam->source === 'desimone' ? 'desimone' : 'scorenco');

            if ($this->source === 'desimone') {
                $this->desimoneCompetitionId = (string) $championship->external_id;
                $this->desimoneTeamId = (string) $championshipTeam->external_id;
                $this->loadDesimoneTeams();
                $this->desimoneSelection = $this->desimoneCompetitionId;
            } else {
                $this->scorencoteamid = $championshipTeam->external_id ?? '';
            }
        } else {
            $this->authorize('create', Championship::class);
        }
    }

    public function updatedSource(string $source): void
    {
        $this->desimoneTeams = [];
        $this->desimoneSelection = '';
        $this->desimoneCompetitionId = '';
        $this->desimonePouleId = '';
        $this->desimoneTeamId = '';

        if ($source === 'desimone') {
            $this->loadDesimoneTeams();
        }
    }

    public function updatedDesimoneSelection(string $selection): void
    {
        $selectedTeam = collect($this->desimoneTeams)
            ->firstWhere('poule_id', $selection);

        if (! $selectedTeam) {
            $this->desimoneCompetitionId = '';
            $this->desimonePouleId = '';
            $this->desimoneTeamId = '';

            return;
        }

        $competition = app(DesimoneService::class)->getCompetition($selectedTeam['competition_id'],$selectedTeam['poule_id']);
        $pool = collect($competition['phases'] ?? [])
            ->flatMap(fn (array $phase): array => $phase['poules'] ?? [])
            ->firstWhere('id', $selectedTeam['poule_id']);

        if (! $pool) {
            throw new \UnexpectedValueException('La poule sélectionnée est absente de la réponse Desimone.');
        }

        $this->name = (string) ($competition['nom'] ?? $selectedTeam['name']);
        $this->season = $this->currentSeason();
        $this->category = (string) data_get($competition, 'categorie.code', '');
        $this->division = (string) ($competition['code'] ?? '');
        $this->pool = (string) ($pool['nom'] ?? '');
        $this->desimoneCompetitionId = $selectedTeam['competition_id'];
        $this->desimonePouleId = $selectedTeam['poule_id'];
        $this->desimoneTeamId = $selectedTeam['engagement_id'];
    }

    private function loadDesimoneTeams(): void
    {
        $this->desimoneTeams = app(DesimoneService::class)->getTeams(self::DESIMONE_CLUB_ID);
    }

    private function currentSeason(): string
    {
        $year = now()->month >= 8 ? now()->year : now()->year - 1;

        return $year.'-'.($year + 1);
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize(
            $this->championship ? 'update' : 'create',
            $this->championship ?? Championship::class
        );

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'season' => ['required', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'pool' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'in:scorenco,desimone'],
            'scorencoteamid' => ['required_if:source,scorenco', 'nullable', 'string', 'max:255'],
            'desimoneSelection' => ['required_if:source,desimone', 'nullable', 'string', 'max:255'],
        ]);

        $selectedDesimoneTeam = null;
        if ($validated['source'] === 'desimone') {
            $selectedDesimoneTeam = collect(app(DesimoneService::class)->getTeams(self::DESIMONE_CLUB_ID))
                ->firstWhere('poule_id', $validated['desimoneSelection']);

            if (! $selectedDesimoneTeam) {
                throw ValidationException::withMessages([
                    'desimoneSelection' => 'Sélectionnez un championnat valide du club.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $selectedDesimoneTeam): void {
            $externalTeamId = match ($validated['source']) {
                'scorenco' => $validated['scorencoteamid'],
                'desimone' => $selectedDesimoneTeam['engagement_id'],
                default => null,
            };
            $attributes = [
                'name' => $validated['name'],
                'season' => $validated['season'],
                'category' => $validated['category'] ?: null,
                'division' => $validated['division'] ?: null,
                'pool' => $validated['pool'] ?: null,
                'source' => $validated['source'],
                'external_id' => $selectedDesimoneTeam['competition_id'],
                'pool_external_id' => $selectedDesimoneTeam['poule_id'],
            ];

            if ($this->championship) {
                $this->championship->update($attributes);
                $this->championship->teams()
                    ->where('team_id', $this->team->id)
                    ->firstOrFail()
                    ->update([
                        'name' => $this->team->name,
                        'source' => $validated['source'],
                        'external_id' => $externalTeamId,
                    ]);

                return;
            }

            $championship = Championship::create([
                ...$attributes,
                'status' => 'active',
            ]);

            $championship->teams()->create([
                'team_id' => $this->team->id,
                'name' => $this->team->name,
                'source' => $validated['source'],
                'external_id' => $externalTeamId,
            ]);
        });
      

        session()->flash('success', $this->championship ? 'Championnat modifié.' : 'Championnat créé.');

        return $this->redirectRoute('championship.show', ['team' => $this->team]);
    }

    public function render()
    {
        return view('livewire.championship.manage')
            ->layout('layouts.app');
    }
}
