<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;

class Accept extends Component
{
    public $invitation;

    public $name="";
    public $firstname="";
    public $email="";
    public $password="";
    public $password_confirmation="";
    
    public function render()
    {
        return view('livewire.user.accept')
            ->layout('layouts.app');
    }
    
    public function mount($token)
    {
        $this->invitation = \App\Models\Invitation::where('token', $token)->firstOrFail();

        if ($this->invitation->used) {
            abort(403, 'Invitation déjà utilisée');
        }

        if ($this->invitation->expires_at) {
            $expire = new \Carbon\Carbon($this->invitation->expires_at);
            if ($expire->isPast()) {
                abort(403, 'Invitation expirée');
            }
        }

        //on stocke l'invit en session au cas ou on fait du google
        session(['invitation_id' => $this->invitation->id]);

    }

    public function store()
    {
        //on a plus besoin de cette session
        session()->forget('invitation_id');

        if ($this->invitation->used) {
            abort(403);
        }

        $this->validate([
            'firstname' => 'required|string|min:2',
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = \App\Models\User::findOrFail($this->invitation->user_id);

        $user->update([
            'name' => $this->name,
            'firstname' => $this->firstname,
            'email' => strtolower($this->email),
            'password' => Hash::make($this->password)
        ]);

        // marquer invitation utilisée
        /*$this->invitation->update([
            'used' => true
        ]);*/
        $this->invitation->delete();

        Auth::login($user,true);

        return redirect()->route('dashboard');
    }
}