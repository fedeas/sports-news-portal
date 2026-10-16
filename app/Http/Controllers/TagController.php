<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class TagController extends Controller
{
    public const ARTICLES_PER_PAGE = 20;

    /**
     * Show every live article filed under a topic.
     */
    public function show(string $slug): View
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();

        $articles = Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->whereHas('tags', fn (Builder $query) => $query->whereKey($tag->id))
            ->with(Article::CARD_RELATIONS)
            ->latest('published_at')
            ->paginate(self::ARTICLES_PER_PAGE);

        return view('site.tags.show', [
            'tag' => $tag,
            'articles' => $articles,
            'canonical' => $articles->currentPage() > 1
                ? $tag->url().'?page='.$articles->currentPage()
                : $tag->url(),
        ]);
    }
}
