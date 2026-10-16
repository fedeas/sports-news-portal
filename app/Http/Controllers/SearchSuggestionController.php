<?php

namespace App\Http\Controllers;

use App\Actions\SearchArticles;
use App\Actions\SearchTeams;
use App\Livewire\Search;
use App\Models\Article;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchSuggestionController extends Controller
{
    public const ARTICLES = 6;

    public const TEAMS = 3;

    /**
     * Suggestions for the search bar, fetched as the visitor types.
     *
     * Only what the dropdown shows is returned, as plain strings and URLs.
     */
    public function __invoke(Request $request, SearchArticles $searchArticles, SearchTeams $searchTeams): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:'.(SearchArticles::MAX_LENGTH * 2)],
        ]);

        $term = Str::of($validated['q'] ?? '')->squish()->limit(SearchArticles::MAX_LENGTH, '')->value();

        if (mb_strlen($term) < Search::MIN_LENGTH) {
            return response()->json(['articles' => [], 'teams' => []]);
        }

        return response()->json([
            'articles' => $searchArticles->handle($term)
                ->limit(self::ARTICLES)
                ->get()
                ->map(fn (Article $article): array => [
                    'title' => $article->title,
                    'url' => $article->url(),
                    'category' => $article->category->name,
                    'author' => $article->author->name,
                    'image' => $article->featuredImage?->smallestUrl(),
                ]),
            'teams' => $searchTeams->handle($term)
                ->limit(self::TEAMS)
                ->get(['id', 'name', 'slug'])
                ->map(fn (Team $team): array => ['name' => $team->name, 'url' => $team->url()]),
        ]);
    }
}
