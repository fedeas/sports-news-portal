<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlugHistory>
 */
class SlugHistoryFactory extends Factory
{
    /**
     * Define the model's default state: an old slug of an article.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sluggable_type' => (new Article)->getMorphClass(),
            'sluggable_id' => Article::factory(),
            'slug' => fake()->unique()->slug(3),
        ];
    }
}
