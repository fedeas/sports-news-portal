<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class NewsController extends Controller
{
    public const ARTICLES_PER_PAGE = 30;

    /**
     * The running news feed: every live story on the site, newest first, grouped by day.
     */
    public function index(): View
    {
        $articles = Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(Article::CARD_RELATIONS)
            ->latest('published_at')
            ->paginate(self::ARTICLES_PER_PAGE);

        return view('site.news.index', [
            'articles' => $articles,
            // Days as the reader's calendar has them, not as UTC does.
            'days' => $articles->getCollection()->groupBy(
                fn (Article $article): string => $article->published_at->timezone(config('app.display_timezone'))->toDateString(),
            ),
            'canonical' => $articles->currentPage() > 1
                ? route('news.index').'?page='.$articles->currentPage()
                : route('news.index'),
        ]);
    }
}
