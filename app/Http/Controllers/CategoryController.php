<?php

namespace App\Http\Controllers;

use App\Actions\BuildArticlePage;
use App\Actions\RecordArticleView;
use App\Models\Article;
use App\Models\Category;
use App\Models\SlugHistory;
use App\Services\ContentCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class CategoryController extends Controller
{
    public const ARTICLES_PER_PAGE = 20;

    /**
     * Listing pages whose article ids are cached; deeper pages are rarely visited and always queried.
     */
    public const CACHED_PAGES = 5;

    public function __construct(
        private ContentCache $contentCache,
        private BuildArticlePage $buildArticlePage,
        private RecordArticleView $recordArticleView,
    ) {}

    /**
     * Show a top-level category page at /{category}.
     *
     * A subcategory requested at the top level is permanently redirected to /{parent}/{child}.
     */
    public function show(string $category): View|RedirectResponse
    {
        $current = Category::query()->active()->with('parent')->where('slug', $category)->first();

        if ($current === null) {
            $renamed = SlugHistory::findPreviousOwner(Category::class, $category);

            abort_unless($renamed !== null && $this->isVisible($renamed), 404);

            return redirect($renamed->url(), 301);
        }

        if ($current->parent_id !== null) {
            abort_unless($this->isVisible($current), 404);

            return redirect($current->url(), 301);
        }

        return $this->categoryPage($current);
    }

    /**
     * Resolve /{category}/{slug}: a subcategory of that category first, otherwise an article.
     *
     * Category and article slugs are unique across both tables, so the lookup is unambiguous.
     * Anything found under another path (a renamed parent, the wrong category or an old slug)
     * is permanently redirected to its canonical URL, but only when it is still live.
     */
    public function showPath(string $category, string $slug): View|RedirectResponse
    {
        $parent = Category::query()->active()->whereNull('parent_id')->where('slug', $category)->first();

        $subcategory = $parent?->children()->active()->where('slug', $slug)->first();

        if ($subcategory !== null) {
            return $this->categoryPage($subcategory->setRelation('parent', $parent));
        }

        $article = $this->liveArticles()
            ->with(BuildArticlePage::relations())
            ->where('slug', $slug)
            ->first();

        if ($article !== null) {
            if ($article->category->slug !== $category) {
                return redirect($article->url(), 301);
            }

            $this->recordArticleView->handle($article, request());

            return $this->articlePage($article);
        }

        $movedSubcategory = Category::query()->active()->whereNotNull('parent_id')->with('parent')->where('slug', $slug)->first()
            ?? SlugHistory::findPreviousOwner(Category::class, $slug);

        if ($movedSubcategory?->parent_id !== null && $this->isVisible($movedSubcategory)) {
            return redirect($movedSubcategory->url(), 301);
        }

        $renamedArticle = SlugHistory::findPreviousOwner(Article::class, $slug);

        abort_if($renamedArticle === null, 404);

        $liveArticle = $this->liveArticles()->with('category')->find($renamedArticle->id);

        abort_if($liveArticle === null, 404);

        return redirect($liveArticle->url(), 301);
    }

    /**
     * Published articles in active categories.
     *
     * @return Builder<Article>
     */
    private function liveArticles(): Builder
    {
        return Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active());
    }

    private function isVisible(Category $category): bool
    {
        return $category->is_active && ($category->parent_id === null || $category->parent?->is_active);
    }

    private function categoryPage(Category $category): View
    {
        // A section page also lists the articles of its subsections; a subsection page lists only its own.
        $children = $category->parent_id === null
            ? $category->children()->active()->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $section = $category->parent ?? $category;

        $articles = $this->categoryArticles($category, $children->pluck('id')->all());

        // The whole row of subsections, so a reader can switch country without going back.
        $subcategories = $category->parent_id === null
            ? $children
            : $section->children()->active()->orderBy('sort_order')->orderBy('name')->get();

        // Each one builds its own /{section}/{subsection} URL from the parent it already has.
        $subcategories->each(fn (Category $subcategory) => $subcategory->setRelation('parent', $section));

        return view('site.categories.show', [
            'category' => $category,
            'section' => $section,
            'subcategories' => $subcategories,
            'articles' => $articles,
            'canonical' => $articles->currentPage() > 1
                ? $category->url().'?page='.$articles->currentPage()
                : $category->url(),
        ]);
    }

    /**
     * A page of live articles in a category and its subcategories.
     *
     * The first pages cache only the article ids and the total; the models are loaded fresh.
     *
     * @param  array<int, int>  $subcategoryIds
     * @return LengthAwarePaginator<int, Article>
     */
    private function categoryArticles(Category $category, array $subcategoryIds): LengthAwarePaginator
    {
        $query = Article::query()
            ->published()
            ->whereIn('category_id', [$category->id, ...$subcategoryIds]);

        $page = Paginator::resolveCurrentPage();

        if ($page > self::CACHED_PAGES) {
            return $query->with(Article::CARD_RELATIONS)->latest('published_at')->paginate(self::ARTICLES_PER_PAGE);
        }

        ['ids' => $ids, 'total' => $total] = $this->contentCache->remember(
            [ContentCache::ARTICLES, ContentCache::CATEGORIES],
            "category:{$category->id}:page:{$page}:v1",
            fn (): array => [
                'ids' => (clone $query)->latest('published_at')->forPage($page, self::ARTICLES_PER_PAGE)->pluck('id')->all(),
                'total' => (clone $query)->count(),
            ],
        );

        $items = Article::query()
            ->published()
            ->whereKey($ids)
            ->with(Article::CARD_RELATIONS)
            ->get()
            ->sortBy(fn (Article $article) => array_search($article->id, $ids, true))
            ->values();

        return new LengthAwarePaginator($items, $total, self::ARTICLES_PER_PAGE, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    private function articlePage(Article $article): View
    {
        return view('site.articles.show', $this->buildArticlePage->handle($article));
    }
}
