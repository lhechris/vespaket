<?php

namespace App\Livewire\Gymnase;

use App\Models\Place;
use Livewire\Component;

class Show extends Component
{
    public $places;

    public ?Place $editingPlace = null;

    public int $placesPage = 1;

    public int $placesLastPage = 1;

    private const PLACES_PER_PAGE = 10;

    public function mount(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $query = Place::query()->orderBy('city');

        $this->placesLastPage = max(1, (int) ceil($query->count() / self::PLACES_PER_PAGE));
        $this->placesPage = min($this->placesPage, $this->placesLastPage);
        $this->places = $query
            ->forPage($this->placesPage, self::PLACES_PER_PAGE)
            ->get();
    }

    public function previousPlacesPage(): void
    {
        if ($this->placesPage > 1) {
            $this->placesPage--;
            $this->editingPlace = null;
            $this->loadData();
        }
    }

    public function nextPlacesPage(): void
    {
        if ($this->placesPage < $this->placesLastPage) {
            $this->placesPage++;
            $this->editingPlace = null;
            $this->loadData();
        }
    }

    public function create(): void
    {
        $this->editingPlace = null;
    }

    public function edit(int $id): void
    {
        $this->editingPlace = Place::findOrFail($id);
    }

    public function delete(int $id): void
    {
        Place::findOrFail($id)->delete();
        $this->loadData();
    }

    public function refreshPlaces(): void
    {
        $this->loadData();
        $this->editingPlace = null;
    }

    protected $listeners = ['gymnase-saved' => 'refreshPlaces'];

    public function render()
    {
        return view('livewire.gymnase.show')->layout('layouts.app');
    }
}
