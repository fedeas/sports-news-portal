<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Author;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AuthorController extends Controller
{
    public const ARTICLES_PER_PAGE = 20;

    public const AUTHORS_PER_PAGE = 60;

    /**
     * List the writers with at least one live article.
     */
    public function index(): View
    {
        return view('site.authors.index', [
            'authors' => Author::query()
                ->where('is_active', true)
                ->whereHas('articles', fn (Builder $query) => $query->published())
                ->with('photo')
                ->orderBy('name')
                ->paginate(self::AUTHORS_PER_PAGE),
        ]);
    }

    /**
     * Show a writer with their live articles, as the primary byline or as a co-author.
     */
    public function show(string $slug): View
    {
        $author = Author::query()->where('is_active', true)->with('photo')->where('slug', $slug)->firstOrFail();

        $articles = Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->where(fn (Builder $query) => $query
                ->where('author_id', $author->id)
                ->orWhereHas('coAuthors', fn (Builder $coAuthors) => $coAuthors->whereKey($author->id)))
            ->with(Article::CARD_RELATIONS)
            ->latest('published_at')
            ->paginate(self::ARTICLES_PER_PAGE);

        return view('site.authors.show', [
            'author' => $author,
            'articles' => $articles,
            'canonical' => $articles->currentPage() > 1
                ? $author->url().'?page='.$articles->currentPage()
                : $author->url(),
            'structuredData' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $author->name,
                'url' => $author->url(),
                'description' => $author->bio,
                'image' => $author->photo?->largestUrl(),
                'sameAs' => array_values($author->socialLinks()) ?: null,
            ], fn (mixed $value) => $value !== null),
        ]);
    }
}
