<?php
// mon-test.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Game;

$games = Game::all();

foreach ($games as $game) {
    $teamHome="";
    $teamAway = "";
    $scoreHome = null;
    $scoreAway = null;

    $titre = explode('-',$game->titre,2);
    if (count($titre)==2) {
        $teamHome = trim($titre[0]);
        $teamAway = trim($titre[1]);
    } else if (count($titre)==1) {
        $teamHome = $titre[0];
    }

    $titre = explode('-',$game->score,2);
    if (count($titre)==2) {
        $scoreHome = trim($titre[0]);
        $scoreAway = trim($titre[1]);
    } else if (count($titre)==1) {
        $scoreHome = trim($titre[0]);
    }

    if ($teamHome == "ASLB") { $teamHome=$game->team->name;}
    if ($teamAway == "ASLB") { $teamAway=$game->team->name;}

    $game->update([
        "team_home" => $teamHome,
        "team_away" => $teamAway,
        "score_home" => $scoreHome,
        "score_away" => $scoreAway,
    ]);
    
}

