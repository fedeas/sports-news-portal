<?php

namespace App\Actions;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Stevebauman\Purify\Facades\Purify;

class SaveArticle
{
    public function __construct(
        private FindOrCreateTag $findOrCreateTag,
        private PublishArticle $publishArticle,
    ) {}

    /**
     * Persist an article with its tags, co-authors and status.
     *
     * The body is always purified here, so no unsanitised HTML can reach the database.
     * A `Published` status publishes now, or schedules the article when `publish_at` is in the future.
     *
     * @param  array{
     *     title: string,
     *     slug: string,
     *     excerpt: ?string,
     *     body: string,
     *     category_id: int,
     *     author_id: int,
     *     co_author_ids: array<int, int>,
     *     tag_names: array<int, string>,
     *     team_ids: array<int, int>,
     *     league_ids: array<int, int>,
     *     is_breaking: bool,
     *     is_featured: bool,
     *     meta_title: ?string,
     *     meta_description: ?string,
     *     canonical_url: ?string,
     *     status: ArticleStatus,
     *     publish_at: ?CarbonInterface,
     * }  $data
     */
    public function handle(Article $article, array $data, User $user): Article
    {
        return DB::transaction(function () use ($article, $data, $user) {
            $article->fill([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'excerpt' => $data['excerpt'] ?: null,
                'body' => Purify::config('article')->clean($data['body']),
                'category_id' => $data['category_id'],
                'author_id' => $data['author_id'],
                'is_breaking' => $data['is_breaking'],
                'is_featured' => $data['is_featured'],
                'meta_title' => $data['meta_title'] ?: null,
                'meta_description' => $data['meta_description'] ?: null,
                'canonical_url' => $data['canonical_url'] ?: null,
            ]);

            if (! $article->exists) {
                $article->created_by = $user->id;
            }

            if ($data['status'] === ArticleStatus::Published) {
                $this->publishArticle->handle($article, $data['publish_at'] ?? now());
            } else {
                $article->status = $data['status'];
                $article->save();
            }

            $article->tags()->sync(
                collect($data['tag_names'])
                    ->map(fn (string $name) => $this->findOrCreateTag->handle($name)->id)
                    ->unique()
                    ->values()
                    ->all(),
            );

            $article->coAuthors()->sync(
                collect($data['co_author_ids'])
                    ->reject(fn (int $authorId) => $authorId === $data['author_id'])
                    ->unique()
                    ->values()
                    ->mapWithKeys(fn (int $authorId, int $position) => [$authorId => ['sort_order' => $position]])
                    ->all(),
            );

            $article->teams()->sync(array_values(array_unique($data['team_ids'])));
            $article->leagues()->sync(array_values(array_unique($data['league_ids'])));

            return $article;
        });
    }
}
