<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Services\ChampionshipService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_it_refreshes_all_scorenco_gamedays_scheduled_for_today(): void
    {
        Carbon::setTestNow('2026-11-06 12:00:00');

        try {
            $championships = collect(['Championnat A', 'Championnat B'])
                ->map(fn (string $name): Championship => Championship::create([
                    'name' => $name,
                    'season' => '2026-2027',
                ]));

            $firstMatchday = ChampionshipGamedays::create([
                'championship_id' => $championships[0]->id,
                'number' => 1,
                'label' => 'Journée 1',
                'date' => '2026-11-07',
                'source' => 'scorenco',
                'external_id' => '14284752',
            ]);
            $secondMatchday = ChampionshipGamedays::create([
                'championship_id' => $championships[1]->id,
                'number' => 1,
                'label' => 'Journée 1',
                'date' => '2026-11-05',
                'source' => 'scorenco',
                'external_id' => '14284753',
            ]);
            $matchdayWithNoGamesToday = ChampionshipGamedays::create([
                'championship_id' => $championships[0]->id,
                'number' => 2,
                'date' => '2026-11-06',
                'source' => 'scorenco',
                'external_id' => '14284754',
            ]);
            ChampionshipGamedays::create([
                'championship_id' => $championships[1]->id,
                'number' => 2,
                'date' => '2026-11-06',
                'source' => 'scorenco',
                'external_id' => 'date:2026-11-06',
            ]);

            $firstHomeTeam = ChampionshipTeam::create([
                'championship_id' => $championships[0]->id,
                'name' => 'Équipe locale A',
            ]);
            $firstAwayTeam = ChampionshipTeam::create([
                'championship_id' => $championships[0]->id,
                'name' => 'Équipe visiteuse A',
            ]);
            ChampionshipGames::create([
                'matchday_id' => $firstMatchday->id,
                'home_team_id' => $firstHomeTeam->id,
                'away_team_id' => $firstAwayTeam->id,
                'scheduled_at' => '2026-11-06 19:00:00',
                'status' => 'scheduled',
                'source' => 'scorenco',
                'external_id' => 'existing-match-1',
            ]);
            ChampionshipGames::create([
                'matchday_id' => $firstMatchday->id,
                'home_team_id' => $firstHomeTeam->id,
                'away_team_id' => $firstAwayTeam->id,
                'scheduled_at' => '2026-11-06 20:00:00',
                'status' => 'scheduled',
                'source' => 'scorenco',
                'external_id' => 'existing-match-2',
            ]);

            $secondHomeTeam = ChampionshipTeam::create([
                'championship_id' => $championships[1]->id,
                'name' => 'Équipe locale B',
            ]);
            $secondAwayTeam = ChampionshipTeam::create([
                'championship_id' => $championships[1]->id,
                'name' => 'Équipe visiteuse B',
            ]);
            ChampionshipGames::create([
                'matchday_id' => $secondMatchday->id,
                'home_team_id' => $secondHomeTeam->id,
                'away_team_id' => $secondAwayTeam->id,
                'scheduled_at' => '2026-11-06 21:00:00',
                'status' => 'scheduled',
                'source' => 'scorenco',
                'external_id' => 'existing-match-3',
            ]);

            ChampionshipGames::create([
                'matchday_id' => $matchdayWithNoGamesToday->id,
                'home_team_id' => $firstHomeTeam->id,
                'away_team_id' => $firstAwayTeam->id,
                'scheduled_at' => '2026-11-07 19:00:00',
                'status' => 'scheduled',
                'source' => 'scorenco',
                'external_id' => 'existing-match-4',
            ]);

            Http::fake([
                'graphql.scorenco.com/*' => Http::sequence()
                    ->push(['data' => [
                        'competitions_event_detail_by_round_day_id' => [$this->scorencoMatch(1)],
                    ]])
                    ->push(['data' => [
                        'competitions_event_detail_by_round_day_id' => [$this->scorencoMatch(2)],
                    ]]),
            ]);

            $importedGames = app(ChampionshipService::class)->refreshGamedays();

            $this->assertSame(2, $importedGames);
            $this->assertDatabaseCount('championship_gamedays', 4);
            $this->assertDatabaseCount('championship_games', 6);
            $this->assertDatabaseHas('championship_games', [
                'source' => 'scorenco',
                'external_id' => 'match-1',
            ]);
            $this->assertDatabaseHas('championship_games', [
                'source' => 'scorenco',
                'external_id' => 'match-2',
            ]);
            Http::assertSentCount(2);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function scorencoMatch(int $id): array
    {
        return [
            'id' => 'match-'.$id,
            'status' => 'scheduled',
            'date' => '2026-11-06',
            'time' => '2026-11-06T19:00:00+01:00',
            'teams' => [
                [
                    'role' => 'home',
                    'team_id' => 100 + $id,
                    'name_in_competition' => 'Équipe domicile '.$id,
                ],
                [
                    'role' => 'away',
                    'team_id' => 200 + $id,
                    'name_in_competition' => 'Équipe extérieur '.$id,
                ],
            ],
        ];
    }
}
