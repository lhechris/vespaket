<?php

namespace App\Console\Commands\User;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

#[Signature('user:create')]
#[Description("Creation d'un utilisateur")]
class Create extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Nom');
        $firstname = $this->ask('Prénom');
        $email = $this->ask('Email');
        $password = $this->secret('Mot de passe');
        $role = $this->ask("Role (".User::ROLE_ADMIN.",".User::ROLE_COACH.",".User::ROLE_PARENT.",".User::ROLE_PLAYER.")");
        
        $user = User::create([
            'name' => $name,
            'firstname' => $firstname,
            'email' => strtolower($email),
            'password' => Hash::make($password),
            'role' => $role,
        ]);        
    }
}
