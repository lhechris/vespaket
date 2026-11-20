<?php

namespace Database\Factories;

use App\Models\Licensee;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => Member::TYPE_PLAYER,
            'numero' => fake()->randomNumber(2,false),
            'licensee_id' => Licensee::factory(),
        ];
    }
}
