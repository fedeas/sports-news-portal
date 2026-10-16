<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Author;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class BuildArticlePage
{
    public const RELATED_LIMIT = 3;

    /**
     * The relations the article page renders.
     *
     * @return array<int|string, string|\Closure>
     */
    public static function relations(): array
    {
        return [
            'category.parent', 'author', 'coAuthors', 'tags', 'featuredImage', 'ogImage',
            'teams' => fn (MorphToMany $query) => $query->active()->orderBy('name'),
            'leagues' => fn (MorphToMany $query) => $query->active()->orderBy('name'),
        ];
    }

    /**
     * The view data for an article page, loaded with {@see self::relations()}.
     *
     * @return array{article: Article, description: string, canonical: string, image: ?string, bylineAuthors: SupportCollection<int, Author>, relatedArticles: Collection<int, Article>, structuredData: array<string, mixed>}
     */
    public function handle(Article $article): array
    {
        $socialImage = $article->ogImage ?? $article->featuredImage;

        return [
            'article' => $article,
            'description' => $article->metaDescription(),
            'canonical' => $article->canonical_url ?? $article->url(),
            'image' => $socialImage?->largestUrl(),
            'bylineAuthors' => collect([$article->author, ...$article->coAuthors])->unique('id')->values(),
            'relatedArticles' => Article::query()
                ->published()
                ->where('category_id', $article->category_id)
                ->whereKeyNot($article->id)
                ->with(Article::CARD_RELATIONS)
                ->latest('published_at')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            'structuredData' => $this->structuredData($article, $socialImage?->largestUrl()),
        ];
    }

    /**
     * schema.org NewsArticle data for search engines.
     *
     * @return array<string, mixed>
     */
    private function structuredData(Article $article, ?string $image): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $article->url()],
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $article->metaDescription(),
            'image' => $image !== null ? [$image] : null,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'articleSection' => $article->category->name,
            'keywords' => $article->tags->map(fn (Tag $tag) => $tag->name)->join(', ') ?: null,
            'author' => collect([$article->author, ...$article->coAuthors])
                ->unique('id')
                ->map(fn (Author $author) => ['@type' => 'Person', 'name' => $author->name])
                ->values()
                ->all(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
        ], fn (mixed $value) => $value !== null);
    }
}
