<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Category;
use App\Services\ContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class BuildHomepage
{
    /**
     * How long an article counts as breaking news on the homepage.
     */
    public const BREAKING_WINDOW_HOURS = 24;

    public const BREAKING_LIMIT = 5;

    public const LATEST_LIMIT = 9;

    public const CATEGORY_BLOCK_LIMIT = 4;

    public const MOST_READ_LIMIT = 5;

    /**
     * How far back an article counts for the most read list.
     */
    public const MOST_READ_WINDOW_DAYS = 30;

    public const CACHE_KEY = 'homepage:v2';

    public function __construct(private ContentCache $contentCache) {}

    /**
     * Assemble every homepage block from live articles.
     *
     * Only the chosen article and category ids are cached; the models are loaded fresh in two
     * queries, so edits show at once and nothing unpublished can be served from a stale entry.
     *
     * @return array{
     *     hero: ?Article,
     *     breaking: Collection<int, Article>,
     *     latest: Collection<int, Article>,
     *     mostRead: Collection<int, Article>,
     *     categoryBlocks: SupportCollection<int, array{category: Category, articles: Collection<int, Article>}>,
     * }
     */
    public function handle(): array
    {
        $layout = $this->contentCache->remember(
            [ContentCache::ARTICLES, ContentCache::CATEGORIES],
            self::CACHE_KEY,
            fn (): array => $this->layout(),
        );

        $articleIds = collect([$layout['hero'], ...$layout['breaking'], ...$layout['latest'], ...$layout['mostRead']])
            ->merge(collect($layout['categoryBlocks'])->flatten())
            ->filter()
            ->unique()
            ->values();

        $articles = $this->listing()->whereKey($articleIds)->get()->keyBy('id');

        $categories = Category::query()
            ->active()
            ->whereKey(array_keys($layout['categoryBlocks']))
            ->get()
            ->keyBy('id');

        $pick = fn (array $ids): Collection => new Collection(
            collect($ids)->map(fn (int $id) => $articles->get($id))->filter()->values()->all(),
        );

        return [
            'hero' => $layout['hero'] !== null ? $articles->get($layout['hero']) : null,
            'breaking' => $pick($layout['breaking']),
            'latest' => $pick($layout['latest']),
            'mostRead' => $pick($layout['mostRead']),
            'categoryBlocks' => collect($layout['categoryBlocks'])
                ->map(fn (array $ids, int $categoryId) => [
                    'category' => $categories->get($categoryId),
                    'articles' => $pick($ids),
                ])
                ->filter(fn (array $block) => $block['category'] !== null && $block['articles']->isNotEmpty())
                ->values(),
        ];
    }

    /**
     * The ids that make up each block, in display order.
     *
     * @return array{hero: ?int, breaking: array<int, int>, latest: array<int, int>, mostRead: array<int, int>, categoryBlocks: array<int, array<int, int>>}
     */
    private function layout(): array
    {
        $hero = $this->hero();

        return [
            'hero' => $hero?->id,
            'breaking' => $this->breaking()->modelKeys(),
            'latest' => $this->latest($hero)->modelKeys(),
            'mostRead' => $this->mostRead()->modelKeys(),
            'categoryBlocks' => $this->categoryBlocks()
                ->mapWithKeys(fn (array $block) => [$block['category']->id => $block['articles']->modelKeys()])
                ->all(),
        ];
    }

    /**
     * The most recent featured article, otherwise the most recent breaking news, otherwise the latest article.
     */
    public function hero(): ?Article
    {
        return $this->listing()->where('is_featured', true)->first()
            ?? $this->recentBreaking()->first()
            ?? $this->listing()->first();
    }

    /**
     * Breaking news from the last day, newest first.
     *
     * @return Collection<int, Article>
     */
    public function breaking(): Collection
    {
        return $this->recentBreaking()->limit(self::BREAKING_LIMIT)->get();
    }

    /**
     * The latest articles across the site, without the hero.
     *
     * @return Collection<int, Article>
     */
    public function latest(?Article $except = null): Collection
    {
        return $this->listing()
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->id))
            ->limit(self::LATEST_LIMIT)
            ->get();
    }

    /**
     * The most read articles of the last month, most read first.
     *
     * @return Collection<int, Article>
     */
    public function mostRead(): Collection
    {
        return $this->listing()
            ->where('published_at', '>=', now()->subDays(self::MOST_READ_WINDOW_DAYS))
            ->where('view_count', '>', 0)
            ->reorder()
            ->orderByDesc('view_count')
            ->latest('published_at')
            ->limit(self::MOST_READ_LIMIT)
            ->get();
    }

    /**
     * One block per top-level section with its latest articles, including its subcategories.
     * Sections without live articles are left out.
     *
     * @return SupportCollection<int, array{category: Category, articles: Collection<int, Article>}>
     */
    public function categoryBlocks(): SupportCollection
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->with('activeChildren:id,parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'category' => $category,
                'articles' => $this->listing()
                    ->whereIn('category_id', [$category->id, ...$category->activeChildren->pluck('id')])
                    ->limit(self::CATEGORY_BLOCK_LIMIT)
                    ->get(),
            ])
            ->filter(fn (array $block) => $block['articles']->isNotEmpty())
            ->values();
    }

    /**
     * Live articles in active categories, newest first, with what the cards need.
     *
     * @return Builder<Article>
     */
    private function listing(): Builder
    {
        return Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(Article::CARD_RELATIONS)
            ->latest('published_at');
    }

    /**
     * @return Builder<Article>
     */
    private function recentBreaking(): Builder
    {
        return $this->listing()
            ->where('is_breaking', true)
            ->where('published_at', '>=', now()->subHours(self::BREAKING_WINDOW_HOURS));
    }
}
