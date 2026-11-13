<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Services\ChampionshipService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function() {
    \Log::info("Mise à jour des championnats");
    $srv = app(ChampionshipService::class);
    $srv->refreshAll();    
})->twiceDaily(10, 18);
