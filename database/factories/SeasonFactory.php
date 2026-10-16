<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(1990, 2030);

        return [
            'league_id' => League::factory(),
            'name' => $startYear.'/'.($startYear + 1),
            'starts_on' => "{$startYear}-08-15",
            'ends_on' => ($startYear + 1).'-05-31',
            'is_current' => false,
            'external_id' => null,
        ];
    }

    /**
     * Indicate that this is the league's current season.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
