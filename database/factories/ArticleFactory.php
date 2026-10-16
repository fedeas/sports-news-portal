<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state: a draft.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'category_id' => Category::factory(),
            'author_id' => Author::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(15),
            'body' => collect(range(1, 3))->map(fn () => '<p>'.fake()->paragraph().'</p>')->implode(''),
            'status' => ArticleStatus::Draft,
            'published_at' => null,
            'is_breaking' => false,
            'is_featured' => false,
            'view_count' => 0,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the article is live on the site.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'published_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the article will be published in the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Scheduled,
            'published_at' => now()->addDay(),
        ]);
    }

    /**
     * Indicate that the article has been taken off the site.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Archived,
            'published_at' => now()->subMonth(),
        ]);
    }

    /**
     * Indicate that the article is breaking news.
     */
    public function breaking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_breaking' => true,
        ]);
    }

    /**
     * Indicate that the article is featured on the homepage.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
