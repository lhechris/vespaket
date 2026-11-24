<?php

namespace Database\Factories;

use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        fake()->locale('fr_FR');

        $prefix = fake()->randomElement([
            'Gymnase',
            'Complexe sportif',
            'Salle',
            'Centre sportif',
            'Palais des sports',
        ]);

        $name = fake()->randomElement([
            '',
            'Jean Moulin',
            'Jean Jaurès',
            'Victor Hugo',
            'Pierre de Coubertin',
            'Nelson Mandela',
            'René Cassin',
            'Louison Bobet',
        ]);

        $label = "$prefix $name";

        return [
            'lat' => fake()->randomFloat(12,43,45),
            'lng' => fake()->randomFloat(12,0,1),
            'address' => fake()->streetAddress(),
            'label' => $label , 
            'cp' => fake()->postcode(),
            'city' => fake()->city(),
        ];
    }
}
