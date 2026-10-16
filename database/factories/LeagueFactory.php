<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word()).' League';

        return [
            'sport_id' => Sport::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'country' => 'CY',
            'tier' => 1,
            'logo_media_id' => null,
            'external_source' => null,
            'external_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the league is hidden from the site.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
