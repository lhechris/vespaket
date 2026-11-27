<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
   /* Volt::route('register', 'pages.auth.register')
        ->name('register');*/

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});

Route::get('/auth/google', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/google/callback', function () {

    $googleUser = Socialite::driver('google')->user();
    $googleRawUser =  $googleUser->getRaw();

    // chercher ou créer utilisateur
    $user = App\Models\User::where('google_id', $googleUser->getId())
        ->orWhere('email', $googleUser->getEmail())
        ->first();


    if (!$user) {
        // L'utilisateur n'existe pas, on cherche s'il y a une invitation en cours.
        \Log::info("Connexion avec un compte inconnu ".$googleUser->getEmail());
        $invitation = App\Models\Invitation::find(session('invitation_id'));

        if (!$invitation) {
            \Log::info("Pas d'invitation en cours pour ".$googleUser->getEmail());
            return redirect()->route('login')->with('error', 'Vous n’avez pas de compte sur cette application. Merci de demander une invitation.');
        }

        session()->forget('invitation_id');

        $user = App\Models\User::findOrFail($invitation->user_id);
        
        if ($user) {
            \Log::info("Ajoute l'utilisateur google par l'invitation".$googleUser->getId());
            \Log::info(print_r($googleRawUser,true));
            $user->update([
                'name' => $googleRawUser['family_name'],
                'firstname' => $googleRawUser['given_name'],
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
            ]);
        }

    } else {
        \Log::info("Utilisateur google existant :".$googleUser->getId());

        $user->update([
            'google_id' => $googleUser->getId()
        ]);
    }
    Auth::login($user,true);

    return redirect('/dashboard');
});

Route::get('logout', function () {
    Auth::logout();
    redirect(route('login'));
});
