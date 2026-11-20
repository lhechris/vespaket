<?php

namespace App\Livewire\User;

use App\Models\Invitation;
use App\Models\Licensee;
use App\Models\User;
use App\Notifications\userNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class Manage extends Component
{
    public array $users = [];
    public $licensees;
    public array $newUser = [
        'name' => '',
        'firstname' => '',
        'email' => '',
        'password' => '',
        'role' => 'parent',
        'selectedLicensees' => [],
    ];
    public bool $creating = false;
    public ?string $link = null;
    public ?int $editingIndex = null;
    public string $licenseeSearch = '';
    public string $userSearch = '';
    public int $usersPage = 1;
    public int $usersLastPage = 1;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $usersQuery = User::with('licensees')->with('invitations')
            ->orderBy('firstname')
            ->orderBy('id');

        $search = trim($this->userSearch);
        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $this->usersLastPage = max(1, (int) ceil($usersQuery->count() / 10));
        $this->usersPage = min($this->usersPage, $this->usersLastPage);

        $this->users = $usersQuery
            ->forPage($this->usersPage, 10)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'firstname' => $user->firstname,
                'email' => $user->email,
                'password' => '',
                'role' => $user->role,
                'selectedLicensees' => $user->licensees->modelKeys(),
                'licenseeNames' => $user->licensees
                    ->map(fn (Licensee $licensee): string => trim($licensee->firstname.' '.$licensee->name))
                    ->join(', '),
                'invitations' => $user->invitations->pluck('token')->join(', '),                
            ])            
            ->all();
        $this->licensees = Licensee::query()
            ->orderBy('firstname')
            ->orderBy('name')
            ->get();
    }

    public function updatedUserSearch(): void
    {
        $this->usersPage = 1;
        $this->editingIndex = null;
        $this->loadData();
    }

    public function previousUsersPage(): void
    {
        if ($this->usersPage > 1) {
            $this->usersPage--;
            $this->editingIndex = null;
            $this->loadData();
        }
    }

    public function nextUsersPage(): void
    {
        if ($this->usersPage < $this->usersLastPage) {
            $this->usersPage++;
            $this->editingIndex = null;
            $this->loadData();
        }
    }

    public function startCreate(): void
    {
        $this->cancelCreate();
        $this->editingIndex = null;
        $this->licenseeSearch = '';
        $this->creating = true;
    }

    public function cancelCreate(): void
    {
        $this->resetValidation();
        $this->licenseeSearch = '';
        $this->newUser = [
            'name' => '',
            'firstname' => '',
            'email' => '',
            'password' => '',
            'role' => 'parent',
            'selectedLicensees' => [],
        ];
        $this->creating = false;
    }

    public function createUser(): void
    {
        $this->validate([
            'newUser.firstname' => ['required', 'string', 'min:2'],
            'newUser.name' => ['required', 'string', 'min:2'],
            'newUser.email' => ['required', 'email', 'unique:users,email'],
            'newUser.password' => ['required', 'string', 'min:6'],
            'newUser.role' => ['required', 'in:player,parent,coach'],
            'newUser.selectedLicensees' => ['array'],
            'newUser.selectedLicensees.*' => ['integer', 'distinct', 'exists:licensees,id'],
        ]);

        $user = User::create([
            'name' => $this->newUser['name'],
            'firstname' => $this->newUser['firstname'],
            'email' => strtolower($this->newUser['email']),
            'password' => Hash::make($this->newUser['password']),
            'role' => $this->newUser['role'],
        ]);
        $this->syncLicensees($user, $this->newUser['selectedLicensees']);
        $this->loadData();
        $this->cancelCreate();
        session()->flash('success', 'Utilisateur créé.');
    }

    public function saveUser(int $index): void
    {

        $user = User::findOrFail($this->users[$index]['id']);
        if (!auth()->user()->isAdmin() && ($user->isAdmin() || $this->users[$index]['role'] === User::ROLE_ADMIN)) {
            $this->addError(
                "users.$index.role",
                __("vespaket.cant_modify_admin")
            );
            return;
        }

        $authorizedroles = User::ROLE_PLAYER.",".
                           User::ROLE_PARENT.",".
                           User::ROLE_COACH;

        if (auth()->user()->isAdmin()) {
            $authorizedroles .= ','.User::ROLE_ADMIN;
        }

        $this->validate([
            "users.$index.firstname" => ['required', 'string', 'min:2'],
            "users.$index.name" => ['required', 'string', 'min:2'],
            "users.$index.email" => ['required', 'email'],
            "users.$index.role" => ['required', 'in:'.$authorizedroles],
            "users.$index.password" => ['nullable', 'string', 'min:6'],
            "users.$index.selectedLicensees" => ['array'],
            "users.$index.selectedLicensees.*" => ['integer', 'distinct', 'exists:licensees,id'],
        ]);

        $data = [
            'name' => $this->users[$index]['name'],
            'firstname' => $this->users[$index]['firstname'],
            'email' => $this->users[$index]['email'],
            'role' => $this->users[$index]['role'],
        ];

        if ($this->users[$index]['password']) {
            $data['password'] = Hash::make($this->users[$index]['password']);
        }

        
        $user->update($data);
        $this->syncLicensees($user, $this->users[$index]['selectedLicensees']);
        $this->editingIndex = null;
        session()->flash('success', 'Utilisateur modifié.');
        $this->loadData();
    }

    private function syncLicensees(User $user, array $selectedLicensees): void
    {
        $user->licensees()->sync($selectedLicensees);
    }


    public function invit(): void
    {
        $this->validate([
            'newUser.firstname' => ['required', 'string', 'min:2'],
            'newUser.name' => ['required', 'string', 'min:2'],
            'newUser.email' => ['nullable', 'email'],
            'newUser.role' => ['required', 'in:player,parent,coach'],
            'newUser.selectedLicensees' => ['array'],
            'newUser.selectedLicensees.*' => ['integer', 'distinct', 'exists:licensees,id'],
        ]);

        $token = Str::uuid();
        $email = $this->newUser['email']
            ?: Str::slug($this->newUser['firstname'].'.'.$this->newUser['name']).'@example.com';
        $user = User::create([
            'name' => $this->newUser['name'],
            'firstname' => $this->newUser['firstname'],
            'email' => strtolower($email),
            'password' => Hash::make(Str::random(40)),
            'role' => $this->newUser['role'],
        ]);

        Invitation::create([
            'email' => $email,
            'token' => $token,
            'created_by' => auth()->id(),
            'user_id' => $user->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->syncLicensees($user, $this->newUser['selectedLicensees']);

        $this->loadData();
        $this->cancelCreate();
        $this->link = url('/invitation/'.$token);

    }

    public function editUser(int $index): void
    {
        $this->creating = false;
        $this->editingIndex = $index;
        $this->licenseeSearch = '';
    }

    public function cancelEdit(): void
    {
        $this->loadData();
        $this->editingIndex = null;
        $this->licenseeSearch = '';
    }

    public function filteredLicensees()
    {
        $search = mb_strtolower(trim($this->licenseeSearch));

        if ($search === '') {
            return $this->licensees;
        }

        return $this->licensees->filter(function (Licensee $licensee) use ($search): bool {
            $licenseeText = mb_strtolower($licensee->firstname.' '.$licensee->name.' '.$licensee->licence);

            return str_contains($licenseeText, $search);
        });
    }

    public function associatedLicensees(array $selectedLicensees)
    {
        return $this->licensees->whereIn('id', $selectedLicensees);
    }

    public function delete($id)
    {
        User::findOrFail($id)->delete();
        $this->loadData();
        $this->editingIndex = null;
    }

     public function sendNotification($userId)
    {
         $user = User::findOrFail($userId);

         \Log::info('sendNotification', [
              'user_id' => $user->id,
              'push_subscriptions' => $user->pushSubscriptions()->count(),
         ]);

         $user->notify(new userNotification());
    }


    public function render()
    {
        return view('livewire.user.manage')
            ->layout('layouts.app');
    }
}