<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipStandingEntries;
use App\Models\ChampionshipStandingSnapshots;
use App\Models\ChampionshipTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipMigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_championship_records_can_be_created_with_their_foreign_keys(): void
    {
        $championship = Championship::create([
            'name' => 'Départemental 2',
            'season' => '2026-2027',
        ]);
        $homeTeam = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe locale',
        ]);
        $awayTeam = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe visiteuse',
        ]);
        $matchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
        ]);
        $game = ChampionshipGames::create([
            'matchday_id' => $matchday->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => 68,
            'away_score' => 54,
            'status' => 'completed',
        ]);
        $snapshot = ChampionshipStandingSnapshots::create([
            'championship_id' => $championship->id,
            'matchday_id' => $matchday->id,
            'captured_at' => now(),
        ]);
        $entry = ChampionshipStandingEntries::create([
            'standing_snapshot_id' => $snapshot->id,
            'championship_team_id' => $homeTeam->id,
            'rank' => 1,
            'played' => 1,
            'won' => 1,
            'points' => 2,
        ]);

        $this->assertDatabaseHas('championship_games', [
            'id' => $game->id,
            'matchday_id' => $matchday->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
        $this->assertDatabaseHas('championship_standing_entries', [
            'id' => $entry->id,
            'standing_snapshot_id' => $snapshot->id,
            'championship_team_id' => $homeTeam->id,
        ]);
    }
}
