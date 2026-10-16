<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'sport_id' => Sport::factory(),
            'parent_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is a subcategory of the given top-level category.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'sport_id' => $parent->sport_id,
        ]);
    }

    /**
     * Indicate that the category is not tied to a sport (e.g. Opinion, Transfers).
     */
    public function crossSport(): static
    {
        return $this->state(fn (array $attributes) => [
            'sport_id' => null,
        ]);
    }

    /**
     * Indicate that the category is hidden from the site.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
