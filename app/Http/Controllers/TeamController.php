<?php

namespace App\Http\Controllers;

use App\Actions\ListFixtures;
use App\Models\Article;
use App\Models\SlugHistory;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class TeamController extends Controller
{
    public const TEAMS_PER_PAGE = 60;

    public const ARTICLES_PER_PAGE = 12;

    /**
     * List the active teams alphabetically.
     */
    public function index(): View
    {
        return view('site.teams.index', [
            'teams' => Team::query()->active()->with('logo')->orderBy('name')->paginate(self::TEAMS_PER_PAGE),
        ]);
    }

    /**
     * Show a team page; an old slug is permanently redirected to the current one.
     */
    public function show(string $slug, ListFixtures $listFixtures): View|RedirectResponse
    {
        $team = Team::query()->active()->with(['logo', 'sport'])->where('slug', $slug)->first();

        if ($team === null) {
            $renamed = SlugHistory::findPreviousOwner(Team::class, $slug);

            abort_unless($renamed?->is_active, 404);

            return redirect($renamed->url(), 301);
        }

        $fixtures = $listFixtures->handle($team);

        return view('site.teams.show', [
            'team' => $team,
            'recentResults' => $fixtures['recent'],
            'upcomingFixtures' => $fixtures['upcoming'],
            'articles' => $team->articles()
                ->published()
                ->whereHas('category', fn (Builder $query) => $query->active())
                ->with(Article::CARD_RELATIONS)
                ->latest('published_at')
                ->paginate(self::ARTICLES_PER_PAGE),
            'structuredData' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'SportsTeam',
                'name' => $team->name,
                'alternateName' => $team->short_name,
                'url' => $team->url(),
                'sport' => $team->sport->name,
                'logo' => $team->logo?->largestUrl(),
                'foundingDate' => $team->founded_year !== null ? (string) $team->founded_year : null,
            ], fn (mixed $value) => $value !== null),
        ]);
    }
}
