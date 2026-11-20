<?php

namespace Database\Factories;

use App\Models\Licensee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Licensee>
 */
class LicenseeFactory extends Factory
{
    protected $model = Licensee::class;

    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'name' => fake()->lastName(),
            'licence' => fake()->optional()->numerify('########'),
        ];
    }
}
