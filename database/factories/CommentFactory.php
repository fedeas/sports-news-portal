<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state: a guest comment awaiting moderation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => (new Article)->getMorphClass(),
            'commentable_id' => Article::factory()->published(),
            'parent_id' => null,
            'user_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'body' => fake()->paragraph(),
            'status' => CommentStatus::Pending,
            'ip_address' => fake()->ipv4(),
            'approved_at' => null,
            'approved_by' => null,
        ];
    }

    /**
     * Indicate that the comment is visible to readers.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the comment is on the given article.
     */
    public function forArticle(Article $article): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => $article->getMorphClass(),
            'commentable_id' => $article->id,
        ]);
    }

    /**
     * Indicate that the comment replies to the given comment, on the same article.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
            'parent_id' => $parent->id,
        ]);
    }
}
