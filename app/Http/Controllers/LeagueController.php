<?php

namespace App\Http\Controllers;

use App\Actions\ListFixtures;
use App\Models\Article;
use App\Models\League;
use App\Models\SlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class LeagueController extends Controller
{
    public const ARTICLES_PER_PAGE = 12;

    /**
     * List the active leagues, top divisions first.
     */
    public function index(): View
    {
        return view('site.leagues.index', [
            'leagues' => League::query()
                ->active()
                ->with('logo')
                ->orderByRaw('tier is null')
                ->orderBy('tier')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show a league page with its seasons; an old slug is permanently redirected to the current one.
     */
    public function show(string $slug, ListFixtures $listFixtures): View|RedirectResponse
    {
        $league = League::query()->active()->with(['logo', 'sport', 'seasons'])->where('slug', $slug)->first();

        if ($league === null) {
            $renamed = SlugHistory::findPreviousOwner(League::class, $slug);

            abort_unless($renamed?->is_active, 404);

            return redirect($renamed->url(), 301);
        }

        $season = $league->seasons->firstWhere('is_current', true) ?? $league->seasons->first();

        $fixtures = $season !== null
            ? $listFixtures->handle($season)
            : ['recent' => collect(), 'upcoming' => collect()];

        return view('site.leagues.show', [
            'league' => $league,
            'season' => $season,
            'standings' => $season?->standings()->with('team.logo')->get() ?? collect(),
            'recentResults' => $fixtures['recent'],
            'upcomingFixtures' => $fixtures['upcoming'],
            'articles' => $league->articles()
                ->published()
                ->whereHas('category', fn (Builder $query) => $query->active())
                ->with(Article::CARD_RELATIONS)
                ->latest('published_at')
                ->paginate(self::ARTICLES_PER_PAGE),
            'structuredData' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'SportsOrganization',
                'name' => $league->name,
                'url' => $league->url(),
                'sport' => $league->sport->name,
                'logo' => $league->logo?->largestUrl(),
            ], fn (mixed $value) => $value !== null),
        ]);
    }
}
