<?php

namespace Tests\Feature;

use App\Livewire\Gymnase\Form;
use App\Livewire\Gymnase\Show;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GymnaseShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_place_passes_it_to_the_form_component(): void
    {
        $place = Place::create([
            'label' => 'Complexe Sportif Test',
            'city' => 'Paris',
            'cp' => '75000',
            'address' => '1 rue de la Paix',
            'lat' => 48.8566,
            'lng' => 2.3522,
        ]);

        Livewire::test(Form::class, ['place' => $place])
            ->assertSet('place', $place)
            ->assertSet('attrlab', 'Complexe Sportif Test')
            ->assertSet('attrad', '1 rue de la Paix')
            ->assertSet('attrci', 'Paris')
            ->assertSet('attrcp', '75000')
            ->assertSet('attrlat', 48.8566)
            ->assertSet('attrlng', 2.3522);
    }

    public function test_places_are_paginated_and_the_page_is_clamped_after_deletion(): void
    {
        $places = collect(range(1, 11))->map(fn (int $number): Place => Place::create([
            'label' => sprintf('Gymnase %02d', $number),
            'address' => 'Adresse '.$number,
            'lat' => 48.0,
            'lng' => 2.0,
            'city' => 'Paris',
            'cp' => '75001',
        ]));

        Livewire::test(Show::class)
            ->assertSet('placesPage', 1)
            ->assertSet('placesLastPage', 2)
            ->assertSee('Gymnase 01')
            ->assertDontSee('Gymnase 11')
            ->call('nextPlacesPage')
            ->assertSet('placesPage', 2)
            ->assertSee('Gymnase 11')
            ->call('delete', $places->last()->id)
            ->assertSet('placesPage', 1)
            ->assertSet('placesLastPage', 1)
            ->assertDontSee('2 / 1');
    }
}
