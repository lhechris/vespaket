<?php

namespace Database\Seeders;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipStandingEntries;
use App\Models\ChampionshipStandingSnapshots;
use App\Models\ChampionshipTeam;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChampionshipSeeder extends Seeder
{
    private const SOURCE = 'demo';

    public function run(): void
    {
        DB::transaction(function (): void {
            $seasonStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
            $season = $seasonStartYear.'-'.($seasonStartYear + 1);
            $championship = Championship::updateOrCreate(
                ['source' => self::SOURCE, 'external_id' => 'demo-championship-'.$season],
                [
                    'name' => 'Championnat départemental',
                    'season' => $season,
                    'category' => 'U13',
                    'division' => 'Division 2',
                    'pool' => 'Poule A',
                    'status' => 'active',
                ]
            );

            $teamNames = [
                'Vespaket U13',
                'Basket Club du Val',
                'AS des Coteaux',
                'Étoile Sportive',
                'Union Basket',
                'Stade Municipal',
                'Basket de la Plaine',
                'Olympique Basket',
            ];
            $demoTeam = Team::updateOrCreate(
                ['name' => 'Vespaket U13 (démo)'],
                ['owner_id' => null]
            );
            $championshipTeams = [];

            foreach ($teamNames as $index => $teamName) {
                $championshipTeams[$index] = ChampionshipTeam::updateOrCreate(
                    [
                        'championship_id' => $championship->id,
                        'source' => self::SOURCE,
                        'external_id' => 'demo-team-'.($index + 1),
                    ],
                    [
                        'team_id' => $index === 0 ? $demoTeam->id : null,
                        'name' => $teamName,
                    ]
                );
            }

            $standings = array_fill(0, count($teamNames), [
                'played' => 0,
                'won' => 0,
                'lost' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'points' => 0,
            ]);

            foreach ($this->schedule() as $roundIndex => $games) {
                $roundNumber = $roundIndex + 1;
                $date = now()->startOfWeek()->subWeeks(4 - $roundIndex)->next('Saturday');
                $matchday = ChampionshipGamedays::updateOrCreate(
                    ['championship_id' => $championship->id, 'number' => $roundNumber],
                    [
                        'label' => 'Journée '.$roundNumber,
                        'date' => $date->toDateString(),
                        'source' => self::SOURCE,
                        'external_id' => 'demo-round-'.$roundNumber,
                    ]
                );

                foreach ($games as $gameIndex => [$homeIndex, $awayIndex]) {
                    $isPlayed = $roundNumber <= 4;
                    $homeScore = $isPlayed ? 56 + (($roundNumber * 7 + $gameIndex * 5) % 23) : null;
                    $awayScore = $isPlayed ? 51 + (($roundNumber * 9 + $gameIndex * 4) % 21) : null;

                    if ($isPlayed && $homeScore === $awayScore) {
                        $homeScore++;
                    }

                    ChampionshipGames::updateOrCreate(
                        [
                            'source' => self::SOURCE,
                            'external_id' => 'demo-game-'.$roundNumber.'-'.($gameIndex + 1),
                        ],
                        [
                            'matchday_id' => $matchday->id,
                            'home_team_id' => $championshipTeams[$homeIndex]->id,
                            'away_team_id' => $championshipTeams[$awayIndex]->id,
                            'scheduled_at' => $date->copy()->setTime(14 + ($gameIndex % 2) * 2, 0),
                            'home_score' => $homeScore,
                            'away_score' => $awayScore,
                            'status' => $isPlayed ? 'completed' : 'scheduled',
                            'location' => $gameIndex % 2 === 0 ? 'Gymnase municipal' : null,
                        ]
                    );

                    if ($isPlayed) {
                        $this->updateStanding($standings, $homeIndex, $awayIndex, $homeScore, $awayScore);
                    }
                }
            }

            $snapshot = ChampionshipStandingSnapshots::updateOrCreate(
                [
                    'source' => self::SOURCE,
                    'external_id' => 'demo-standings-'.$season,
                ],
                [
                    'championship_id' => $championship->id,
                    'matchday_id' => $championship->gamedays()->where('number', 4)->value('id'),
                    'captured_at' => now(),
                ]
            );
            $snapshot->entries()->delete();

            $ranking = collect($standings)
                ->map(fn (array $standing, int $index): array => [
                    ...$standing,
                    'team_index' => $index,
                    'difference' => $standing['points_for'] - $standing['points_against'],
                ])
                ->sortBy([
                    ['points', 'desc'],
                    ['difference', 'desc'],
                    ['points_for', 'desc'],
                ])
                ->values();

            foreach ($ranking as $index => $standing) {
                ChampionshipStandingEntries::create([
                    'standing_snapshot_id' => $snapshot->id,
                    'championship_team_id' => $championshipTeams[$standing['team_index']]->id,
                    'rank' => $index + 1,
                    'played' => $standing['played'],
                    'won' => $standing['won'],
                    'lost' => $standing['lost'],
                    'points_for' => $standing['points_for'],
                    'points_against' => $standing['points_against'],
                    'points' => $standing['points'],
                    'penalty_points' => 0,
                ]);
            }
        });
    }

    private function schedule(): array
    {
        $teams = range(0, 7);
        $rounds = [];

        for ($round = 0; $round < 7; $round++) {
            $games = [];

            for ($pair = 0; $pair < 4; $pair++) {
                $home = $teams[$pair];
                $away = $teams[7 - $pair];

                if (($round + $pair) % 2 === 1) {
                    [$home, $away] = [$away, $home];
                }

                $games[] = [$home, $away];
            }

            $rounds[] = $games;
            $teams = [$teams[0], $teams[7], ...array_slice($teams, 1, 6)];
        }

        return $rounds;
    }

    private function updateStanding(
        array &$standings,
        int $homeIndex,
        int $awayIndex,
        int $homeScore,
        int $awayScore,
    ): void {
        $standings[$homeIndex]['played']++;
        $standings[$awayIndex]['played']++;
        $standings[$homeIndex]['points_for'] += $homeScore;
        $standings[$homeIndex]['points_against'] += $awayScore;
        $standings[$awayIndex]['points_for'] += $awayScore;
        $standings[$awayIndex]['points_against'] += $homeScore;

        if ($homeScore > $awayScore) {
            $standings[$homeIndex]['won']++;
            $standings[$awayIndex]['lost']++;
            $standings[$homeIndex]['points'] += 2;
            $standings[$awayIndex]['points']++;

            return;
        }

        $standings[$awayIndex]['won']++;
        $standings[$homeIndex]['lost']++;
        $standings[$awayIndex]['points'] += 2;
        $standings[$homeIndex]['points']++;
    }
}
