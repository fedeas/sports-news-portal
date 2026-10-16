<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' FC';

        return [
            'sport_id' => Sport::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'short_name' => Str::upper(Str::substr($name, 0, 3)),
            'country' => 'CY',
            'founded_year' => fake()->numberBetween(1900, 2010),
            'logo_media_id' => null,
            'external_source' => null,
            'external_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the team is hidden from the site.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the team was imported from an external sports data provider.
     */
    public function fromProvider(string $source = 'demo-provider'): static
    {
        return $this->state(fn (array $attributes) => [
            'external_source' => $source,
            'external_id' => (string) fake()->unique()->numberBetween(1, 1_000_000),
        ]);
    }
}
