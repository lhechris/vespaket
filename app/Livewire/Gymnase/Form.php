<?php

namespace App\Livewire\Gymnase;

use App\Models\Place;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Form extends Component
{
    public ?Place $place = null;

    //on change le nom des attribut pour eviter une demande de sauvegarde des addresse par le navigateur
    public string $attrad = '';
    public ?float $attrlat = null;
    public ?float $attrlng = null;
    public string $attrlab = '';
    public string $attrcp = '';
    public string $attrci = '';

    public bool $isGeocoding = false;
    public bool $geocodeSuccess = false;
    public ?string $geocodeError = null;

    protected function rules(): array
    {
        return [
            'attrlab' => 'required|string|max:255',
            'attrci' => 'required|string|max:255',
            'attrcp'  => 'string|max:5',
            'attrad' => 'string|max:255',
            'attrlat' => 'numeric|between:-90,90',
            'attrlng' => 'numeric|between:-180,180',
        ];
    }

    public function mount(?Place $place = null): void
    {
        $this->syncFromPlace($place);
    }

    public function updatedPlace(?Place $place): void
    {
        $this->syncFromPlace($place);
    }

    private function syncFromPlace(?Place $place): void
    {
        if (! $place || ! $place->exists) {
            $this->place = null;
            $this->attrad = '';
            $this->attrlat = null;
            $this->attrlng = null;
            $this->attrci = '';
            $this->attrlab = '';
            $this->attrcp = '';
            return;
        }

        $this->place = $place;
        $this->attrad = $place->address;
        $this->attrlat = $place->lat;
        $this->attrlng = $place->lng;
        $this->attrci = $place->city;
        $this->attrlab = $place->label;
        $this->attrcp = $place->cp;
    }

    public function geocode(): void
    {
        $this->geocodeError = null;
        $this->geocodeSuccess = false;

        $this->validateOnly('attrad', [
            'attrad' => 'required|string|max:255',
        ]);

        $this->isGeocoding = true;

        try {
            $response = Http::withHeaders([
                // Nominatim exige un User-Agent identifiable (usage policy)
                'User-Agent' => 'ASLabartheBasket/1.0 (lhechris@gmail.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $this->attrad." ".$this->attrcp." ".$this->attrci,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 0,
            ]);

            if (! $response->successful()) {
                $this->geocodeError = "Erreur lors de la requête de géocodage.";
                return;
            }

            $results = $response->json();

            if (empty($results)) {
                $this->geocodeError = "Aucun résultat trouvé pour cette adresse.";
                return;
            }

            $this->attrlat = (float) $results[0]['lat'];
            $this->attrlng = (float) $results[0]['lon'];
            $this->geocodeSuccess = true;
        } catch (\Exception $e) {
            $this->geocodeError = "Impossible de contacter le service de géocodage.".$e;
        } finally {
            $this->isGeocoding = false;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'address' => $this->attrad,
            'lat' => $this->attrlat,
            'lng' => $this->attrlng,
            'label' => $this->attrlab,
            'city' => $this->attrci,
            'cp' => $this->attrcp,
        ];

        if ($this->place) {
            $this->place->update($data);
        } else {
            $this->place = Place::create($data);
        }

        session()->flash('success', 'Gymnase enregistré avec succès.');

        $this->dispatch('gymnase-saved', gymnaseId: $this->place->id);
    }

    public function render()
    {
        return view('livewire.gymnase.form');
    }
}