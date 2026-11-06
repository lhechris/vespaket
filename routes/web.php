<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Team;
use App\Livewire\Member;
use App\Livewire\Dashboard;
use App\Livewire\User;
use App\Livewire\Game;
use App\Livewire\Training;
use App\Livewire\Event;
use App\Livewire\Gymnase;
use App\Livewire\News;
use App\Livewire\Championship\Index as ChampionshipIndex;
use App\Livewire\Championship\Manage as ChampionshipManage;
use App\Livewire\Championship\Show as ChampionshipShow;
use App\Livewire\Championship\Gameday\Create as GamedayCreate;
use App\Livewire\Championship\Gameday\Edit as GamedayEdit;
use App\Livewire\Championship\Games\Create as ChampionshipGameCreate;
use App\Livewire\Championship\Games\Edit as ChampionshipGameEdit;
use App\Livewire\Championship\Games\Index as ChampionshipGameIndex;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\PushSubscriptionController;

//Route::view('/', 'welcome');
Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('dashboard')
            : view('welcome');
});

/*Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');    
    Route::get('/games/show/{game}', Game\Show::class)->name('game.show');
    Route::get('/events/show/{event}', Event\Show::class)->name('event.show');
    
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->middleware('auth');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->middleware('auth');

    Route::get('/championship/show/{team}', ChampionshipShow::class)->name('championship.show');

});


Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'isCoach'])->group(function () {
    Route::get('/team/{team}', Team\Show::class)->name('team.show');
    Route::get('/team/{team}/members', Team\ManageMembers::class)->name('team.members');
    Route::get('/team/{team}/owners', Team\Owners::class)->name('team.owners');
    Route::get('/members', Member\Manage::class)->name("members");
    Route::get('/users', User\Manage::class)->name("users");

    Route::get('/teams/create', Team\Create::class)->name('teams.create');
    Route::get('/team/{team}/games/create', Game\Create::class)->name('team.games.create');
    Route::get('/team/{team}/trainings/create', Training\Create::class)->name('team.trainings.create');
    Route::get('/team/{team}/events/create', Event\Create::class)->name('team.events.create');

    Route::get('/team/{team}/championships', ChampionshipIndex::class)->name('team.championship.index');
    Route::get('/team/{team}/championship/create', ChampionshipManage::class)->name('team.championship.manage');
    Route::get('/team/{team}/championship/{championship}/edit', ChampionshipManage::class)->name('team.championship.edit');
    Route::get('/team/{team}/championship/{championship}/gamedays/create', GamedayCreate::class)->name('team.championship.gamedays.create');
    Route::get('/team/{team}/championship/{championship}/gamedays/{gameday}/edit', GamedayEdit::class)->name('team.championship.gamedays.edit');
    Route::get('/team/{team}/championship/{championship}/gamedays/{gameday}/games', ChampionshipGameIndex::class)->name('team.championship.gamedays.games.index');
    Route::get('/team/{team}/championship/{championship}/gamedays/{gameday}/games/create', ChampionshipGameCreate::class)->name('team.championship.gamedays.games.create');
    Route::get('/team/{team}/championship/{championship}/gamedays/{gameday}/games/{game}/edit', ChampionshipGameEdit::class)->name('team.championship.gamedays.games.edit');

    Route::get('/events/edit/{event}', Event\Edit::class)->name('event.edit');

    Route::get('/games/edit/{game}', Game\Edit::class)->name('game.edit');
    Route::get('/games/selection/{game}', Game\Selection::class)->name('game.selection');
    Route::get('/trainings/{training}', Training\Show::class)->name('training.show');

    Route::get('/gymnase',Gymnase\Show::class)->name('gymnase.show');
    Route::get('/news', News\Manage::class)->name('news');
});


Route::get('/invitation/{token}', User\Accept::class)->name('invitation.accept');


Route::get('/test', function () {
    $inv = \App\Models\Invitation::all()->first();

    $carbon1 = new \Carbon\Carbon($inv->created_at);
    echo $carbon1."<br/>";
    $carbon2 = new \Carbon\Carbon($inv->expires_at);
    echo $carbon2."<br/>";

   if ($carbon1->isPast()) {
    echo '1 : yes<br/>';    
   } else {
    echo '1 : no<br/>';
   }
   if ($carbon2->isPast()) {
    echo '2 : yes<br/>';    
   } else {
    echo '2 : no<br/>';
   }


});

require __DIR__.'/auth.php';
