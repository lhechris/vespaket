<?php

namespace App\Livewire\Licensee;

use App\Models\Licensee;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public $licensees;

    public ?Licensee $editingLicensee = null;

    public string $search = '';

    public int $licenseesPage = 1;

    public int $licenseesLastPage = 1;

    public function mount(): void
    {
        $this->loadLicensees();
    }

    private function loadLicensees(): void
    {
        $query = Licensee::query()
            ->with('members.team')
            ->orderBy('firstname')
            ->orderBy('name');

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('licence', 'like', "%{$search}%");
            });
        }

        $this->licenseesLastPage = max(1, (int) ceil($query->count() / 10));
        $this->licenseesPage = min($this->licenseesPage, $this->licenseesLastPage);
        $this->licensees = $query
            ->forPage($this->licenseesPage, 10)
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->licenseesPage = 1;
        $this->editingLicensee = null;
        $this->loadLicensees();
    }

    public function previousLicenseesPage(): void
    {
        if ($this->licenseesPage > 1) {
            $this->licenseesPage--;
            $this->editingLicensee = null;
            $this->loadLicensees();
        }
    }

    public function nextLicenseesPage(): void
    {
        if ($this->licenseesPage < $this->licenseesLastPage) {
            $this->licenseesPage++;
            $this->editingLicensee = null;
            $this->loadLicensees();
        }
    }

    public function create(): void
    {
        $this->editingLicensee = null;
    }

    public function edit(int $id): void
    {
        $this->editingLicensee = Licensee::findOrFail($id);
    }

    public function refreshLicensees(): void
    {
        $this->loadLicensees();
        $this->editingLicensee = null;
    }

    public function delete(int $id): void
    {
        Licensee::findOrFail($id)->delete();
        $this->refreshLicensees();
    }

    protected $listeners = ['licensee-saved' => 'refreshLicensees'];

    public function render(): View
    {
        return view('livewire.licensee.index')->layout('layouts.app');
    }
}
