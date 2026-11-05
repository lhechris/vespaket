<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Services\ChampionshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_and_updates_standings_from_completed_games(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $teams = collect(['A', 'B', 'C', 'D'])
            ->mapWithKeys(fn (string $name): array => [
                $name => ChampionshipTeam::create([
                    'championship_id' => $championship->id,
                    'name' => 'Équipe '.$name,
                ]),
            ]);
        $firstMatchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
            'date' => '2026-10-31',
        ]);
        $secondMatchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 2,
            'date' => '2026-11-07',
        ]);

        ChampionshipGames::create([
            'matchday_id' => $firstMatchday->id,
            'home_team_id' => $teams['A']->id,
            'away_team_id' => $teams['B']->id,
            'home_score' => 80,
            'away_score' => 60,
            'status' => 'completed',
        ]);
        $secondGame = ChampionshipGames::create([
            'matchday_id' => $secondMatchday->id,
            'home_team_id' => $teams['B']->id,
            'away_team_id' => $teams['C']->id,
            'home_score' => 70,
            'away_score' => 50,
            'status' => 'completed',
        ]);
        ChampionshipGames::create([
            'matchday_id' => $secondMatchday->id,
            'home_team_id' => $teams['C']->id,
            'away_team_id' => $teams['D']->id,
            'home_score' => 90,
            'away_score' => 40,
            'status' => 'scheduled',
        ]);

        $service = app(ChampionshipService::class);
        $snapshot = $service->refreshStanding($championship);
        $entries = $snapshot->entries()->with('championshipTeam')->get()->keyBy('championshipTeam.name');

        $this->assertSame($secondMatchday->id, $snapshot->matchday_id);
        $this->assertCount(4, $entries);
        $this->assertSame([2, 1, 1, 0], [
            $entries['Équipe B']->played,
            $entries['Équipe A']->played,
            $entries['Équipe C']->played,
            $entries['Équipe D']->played,
        ]);
        $this->assertSame([3, 2, 1, 0], [
            $entries['Équipe B']->points,
            $entries['Équipe A']->points,
            $entries['Équipe C']->points,
            $entries['Équipe D']->points,
        ]);
        $this->assertSame([1, 1, 1, 0], [
            $entries['Équipe B']->won,
            $entries['Équipe B']->lost,
            $entries['Équipe A']->won,
            $entries['Équipe A']->lost,
        ]);
        $this->assertSame([130, 130], [
            $entries['Équipe B']->points_for,
            $entries['Équipe B']->points_against,
        ]);

        $secondGame->update(['home_score' => 40, 'away_score' => 60]);
        $updatedSnapshot = $service->refreshStanding($championship);

        $this->assertSame($snapshot->id, $updatedSnapshot->id);
        $this->assertDatabaseCount('championship_standing_snapshots', 1);
        $this->assertDatabaseCount('championship_standing_entries', 4);
        $this->assertDatabaseHas('championship_standing_entries', [
            'standing_snapshot_id' => $snapshot->id,
            'championship_team_id' => $teams['B']->id,
            'played' => 2,
            'won' => 0,
            'lost' => 2,
            'points' => 2,
        ]);
    }

    public function test_it_creates_zeroed_entries_when_no_games_have_been_played(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $team = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe sans match',
        ]);

        $snapshot = app(ChampionshipService::class)->refreshStanding($championship);

        $this->assertNull($snapshot->matchday_id);
        $this->assertDatabaseHas('championship_standing_entries', [
            'standing_snapshot_id' => $snapshot->id,
            'championship_team_id' => $team->id,
            'rank' => 1,
            'played' => 0,
            'points' => 0,
        ]);
    }
}
