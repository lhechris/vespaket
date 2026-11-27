<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Models\Game;
use App\Models\Place;
use App\Models\Team;
use App\Services\ChampionshipGameImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ChampionshipGameImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_games_for_a_team_even_when_its_name_differs_from_the_championship_name(): void
    {
        [$team, $championship, $gameday, $localTeam, $opponent] = $this->createChampionship();
        $championshipGame = $gameday->games()->create([
            'home_team_id' => $localTeam->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => '2026-11-07 14:30:00',
            'home_score' => 62,
            'away_score' => 54,
            'status' => 'completed',
        ]);

        $service = app(ChampionshipGameImportService::class);

        $this->assertSame(1, $service->import($team, $championship));
        $this->assertSame(0, $service->import($team, $championship));
        $this->assertDatabaseCount('games', 1);
        $this->assertDatabaseHas('games', [
            'team_id' => $team->id,
            'championship_game_id' => $championshipGame->id,
            'team_home' => $localTeam->name,
            'team_away' => $opponent->name,
            'date' => '2026-11-07 14:30:00',
            'score_home' => 62,
            'score_away' => 54,
        ]);
    }

    public function test_it_does_not_duplicate_a_match_when_existing_team_names_are_slightly_different(): void
    {
        [$team, $championship, $gameday, $localTeam, $opponent] = $this->createChampionship();
        $championshipGame = $gameday->games()->create([
            'home_team_id' => $localTeam->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => '2026-11-07 14:30:00',
            'status' => 'scheduled',
        ]);
        $localGame = Game::factory()->create([
            'team_id' => $team->id,
            'date' => '2026-11-07 16:00:00',
            'team_home' => 'ASLB',
            'team_away' => 'Basket Club du Val U13F',
        ]);

        $this->assertSame(0, app(ChampionshipGameImportService::class)->import($team, $championship));
        $this->assertDatabaseCount('games', 1);
        $this->assertDatabaseHas('games', [
            'id' => $localGame->id,
            'championship_game_id' => $championshipGame->id,
        ]);
    }

    public function test_it_normalizes_team_names_by_removing_accents_and_punctuation(): void
    {
        $normalizeName = new ReflectionMethod(ChampionshipGameImportService::class, 'normalizeTeamName');

        $this->assertSame(
            'Équipe U15F Basket Club',
            $normalizeName->invoke(app(ChampionshipGameImportService::class), '  Équipe U15F - Basket Club  ')
        );
        $this->assertSame(
            'Ouest Toulousain Basket 2',
            $normalizeName->invoke(app(ChampionshipGameImportService::class), 'OUEST TOULOUSAIN BASKET - 2')
        );
        $this->assertSame(
            'US Colomiers Basket 1',
            $normalizeName->invoke(app(ChampionshipGameImportService::class), 'U.S. COLOMIERS BASKET - 1')
        );
        $this->assertSame(
            'IE Noe Carbonne Basket 2',
            $normalizeName->invoke(app(ChampionshipGameImportService::class), 'IE - NOE CARBONNE BASKET - 2')
        );
        $this->assertSame(
            'UNO De Pamiers 2',
            $normalizeName->invoke(app(ChampionshipGameImportService::class), 'UN. OL. DE PAMIERS - 2 ')
        );
    }

    public function test_it_finds_a_place_from_a_json_championship_game_location(): void
    {
        $place1 = Place::factory()->create(['label' => 'Gymnase du Collège, et autre','cp' => '31170',]);
        $place2 = Place::factory()->create(['label' => 'Gymnase CES P Labitrie',     'cp' => '31170',]);        

        $findGymnase = new ReflectionMethod(ChampionshipGameImportService::class, 'findGymnase');

        $game1 = new ChampionshipGames([
            'location' => 'JSON:{"libelle":"GYMNASE DU COLLEGE","commune":{"codePostal":"31170"}}',
        ]);
        $foundPlace1 = $findGymnase->invoke(app(ChampionshipGameImportService::class), $game1);

        $game2 = new ChampionshipGames([
            'location' => 'JSON:{"libelle":"GYMNASE  C.E.S P.LABITRIE","commune":{"codePostal":"31170"}}',
        ]);
        $foundPlace2 = $findGymnase->invoke(app(ChampionshipGameImportService::class), $game2);

        $this->assertInstanceOf(Place::class, $foundPlace1);
        $this->assertSame($place1->id, $foundPlace1->id);

        $this->assertInstanceOf(Place::class, $foundPlace2);
        $this->assertSame($place2->id, $foundPlace2->id);

    }



    public function test_it_not_find_a_place_from_different_label_championship_game_location(): void
    {
        $place = Place::factory()->create([
            'label' => 'Salle du Collège, et autre',
            'cp' => '31270',
        ]);
        $game = new ChampionshipGames([
            'location' => 'JSON:{"libelle":"GYMNASE DU COLLEGE","commune":{"codePostal":"31270"}}',
        ]);
        $findGymnase = new ReflectionMethod(ChampionshipGameImportService::class, 'findGymnase');

        $this->assertNull($findGymnase->invoke(app(ChampionshipGameImportService::class), $game));

    }

    public function test_it_not_find_a_place_from_different_cp_championship_game_location(): void
    {
        $place = Place::factory()->create([
            'label' => 'GYMNASE DU COLLEGE',
            'cp' => '99501',
        ]);
        $game = new ChampionshipGames([
            'location' => 'JSON:{"libelle":"GYMNASE DU COLLEGE","commune":{"codePostal":"31270"}}',
        ]);
        $findGymnase = new ReflectionMethod(ChampionshipGameImportService::class, 'findGymnase');

        $this->assertNull($findGymnase->invoke(app(ChampionshipGameImportService::class), $game));

    }
    public function test_it_returns_no_place_for_a_non_json_championship_game_location(): void
    {
        $game = new ChampionshipGames(['location' => 'Gymnase municipal']);
        $findGymnase = new ReflectionMethod(ChampionshipGameImportService::class, 'findGymnase');

        $this->assertNull($findGymnase->invoke(app(ChampionshipGameImportService::class), $game));
    }

    /**
     * @return array{0: Team, 1: Championship, 2: ChampionshipGamedays, 3: ChampionshipTeam, 4: ChampionshipTeam}
     */
    private function createChampionship(): array
    {
        $team = Team::factory()->create(['name' => 'U15F2']);
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $gameday = $championship->gamedays()->create(['number' => 1]);
        $localTeam = $championship->teams()->create([
            'name' => 'U15F2',
        ]);
        $opponent = $championship->teams()->create([
            'name' => 'Basket Club Du Val',
        ]);

        return [$team, $championship, $gameday, $localTeam, $opponent];
    }
}
