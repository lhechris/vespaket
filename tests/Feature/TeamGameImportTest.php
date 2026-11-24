<?php

namespace Tests\Feature;

use App\Livewire\Team\Games;
use App\Livewire\Team\ImportGames;
use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamGameImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_import_matches_with_the_selected_options(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $localTeam = $championship->teams()->create([
            'team_id' => $team->id,
            'name' => 'Équipe locale',
        ]);
        $opponent = $championship->teams()->create([
            'name' => 'Équipe adverse',
        ]);
        $gameday = $championship->gamedays()->create(['number' => 1]);

        $location = 'JSON:{"id":"7292","numero":"233115701","libelle":"GYMNASE JEAN BOUIN","libelle2":"","adresse":"Rue du STADE","adresseComplement":"","commune":{"codePostal":"31270","libelle":"CUGNAUX"},"cartographie":{"latitude":43.54026,"longitude":1.35117}}';

        $gameday->games()->create([
            'home_team_id' => $localTeam->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => '2026-11-07 18:30:00',
            'location' => 'Gymnase municipal',
        ]);

        Livewire::actingAs($owner)
            ->test(ImportGames::class, ['team' => $team])
            ->call('toggleForm')
            ->assertSee('ne seront pas ajoutés une seconde fois')
            ->assertSee('Initialiser l’heure des matchs importés à 00:00')
            ->assertSee('Importer le lieu depuis le championnat')
            ->set('selectedChampionshipId', (string) $championship->id)
            ->set('setTimeToMidnight', true)
            ->set('importLocation', false)
            ->call('importGames')
            ->assertHasNoErrors()
            ->assertSee('1 match importé.')
            ->assertDispatched('games-imported');

        $this->assertDatabaseHas('games', [
            'team_id' => $team->id,
            'team_home' => 'Équipe locale',
            'team_away' => 'Équipe adverse',
            'date' => '2026-11-07 00:00:00',
            'place_id' => null,
        ]);
    }

    public function test_the_team_games_list_exposes_the_import_button(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        Livewire::actingAs($owner)
            ->test(Games::class, ['team' => $team])
            ->assertSee('Importer des matchs');
    }

    public function test_a_team_can_import_matches_with_place_options(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();
        $place = Place::factory()->create([
            "label" => "GYMNASE JEAN BOUIN",
            "cp" => '31270',
        ]);

        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $localTeam = $championship->teams()->create([
            'team_id' => $team->id,
            'name' => 'Équipe locale',
        ]);
        $opponent = $championship->teams()->create([
            'name' => 'Équipe adverse',
        ]);
        $gameday = $championship->gamedays()->create(['number' => 1]);

        $location = 'JSON:{"id":"7292","numero":"233115701","libelle":"GYMNASE JEAN BOUIN","libelle2":"","adresse":"Rue du STADE","adresseComplement":"","commune":{"codePostal":"31270","libelle":"CUGNAUX"},"cartographie":{"latitude":43.54026,"longitude":1.35117}}';

        $gameday->games()->create([
            'home_team_id' => $localTeam->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => '2026-11-07 18:30:00',
            'location' => $location,
        ]);

        Livewire::actingAs($owner)
            ->test(ImportGames::class, ['team' => $team])
            ->call('toggleForm')
            ->assertSee('ne seront pas ajoutés une seconde fois')
            ->assertSee('Initialiser l’heure des matchs importés à 00:00')
            ->assertSee('Importer le lieu depuis le championnat')
            ->set('selectedChampionshipId', (string) $championship->id)
            ->set('setTimeToMidnight', false)
            ->set('importLocation', true)
            ->call('importGames')
            ->assertHasNoErrors()
            ->assertSee('1 match importé.')
            ->assertDispatched('games-imported');

        $this->assertDatabaseHas('games', [
            'team_id' => $team->id,
            'team_home' => 'Équipe locale',
            'team_away' => 'Équipe adverse',
            'date' => '2026-11-07 18:30:00',
            'place_id' => $place->id,
        ]);
    }

}
