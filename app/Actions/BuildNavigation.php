<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use App\Services\ContentCache;

class BuildNavigation
{
    /**
     * Clubs listed under a competition in the menu.
     */
    public const TEAMS_PER_LEAGUE = 20;

    public const CACHE_KEY = 'navigation:v3';

    public function __construct(private ContentCache $contentCache) {}

    /**
     * The whole site in one tree: sections, their subsections, and the competitions and clubs
     * of the same sport.
     *
     * Only names and URLs are cached, never models, and the tree is rebuilt whenever a section,
     * competition or club changes.
     *
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     slug: string,
     *     icon: ?string,
     *     children: array<int, array{name: string, url: string, icon: ?string}>,
     *     leagues: array<int, array{name: string, url: string, teams: array<int, array{name: string, url: string}>}>,
     * }>
     */
    public function handle(): array
    {
        return $this->contentCache->remember(
            [ContentCache::CATEGORIES, ContentCache::FIXTURES],
            self::CACHE_KEY,
            fn (): array => $this->tree(),
        );
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     url: string,
     *     slug: string,
     *     icon: ?string,
     *     children: array<int, array{name: string, url: string, icon: ?string}>,
     *     leagues: array<int, array{name: string, url: string, teams: array<int, array{name: string, url: string}>}>,
     * }>
     */
    private function tree(): array
    {
        $leaguesBySport = $this->leaguesBySport();

        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->with('activeChildren')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => $category->url(),
                'icon' => $category->icon?->value,
                'children' => $category->activeChildren
                    // A subsection's URL is /{section}/{subsection}, so hand it the parent it already has.
                    ->map(fn (Category $child): array => [
                        'name' => $child->name,
                        'url' => $child->setRelation('parent', $category)->url(),
                        'icon' => $child->icon?->value,
                    ])
                    ->all(),
                'leagues' => $leaguesBySport[$category->sport_id] ?? [],
            ])
            ->all();
    }

    /**
     * Active competitions with the clubs of their current season, grouped by sport.
     *
     * @return array<int, array<int, array{name: string, url: string, teams: array<int, array{name: string, url: string}>}>>
     */
    private function leaguesBySport(): array
    {
        $teamsByLeague = $this->teamsByLeague();

        return League::query()
            ->active()
            // Home competitions first, then by division, and cups (which have no tier) last.
            ->orderByRaw('country <> ? or country is null', [config('app.home_country')])
            ->orderByRaw('tier is null')
            ->orderBy('tier')
            ->orderBy('name')
            ->get(['id', 'sport_id', 'name', 'slug'])
            ->groupBy('sport_id')
            ->map(fn ($leagues) => $leagues
                ->map(fn (League $league): array => [
                    'name' => $league->name,
                    'url' => $league->url(),
                    'teams' => $teamsByLeague[$league->id] ?? [],
                ])
                ->all())
            ->all();
    }

    /**
     * The clubs playing the current season of each competition.
     *
     * @return array<int, array<int, array{name: string, url: string}>>
     */
    private function teamsByLeague(): array
    {
        return Season::query()
            ->where('is_current', true)
            ->with(['teams' => fn ($query) => $query->active()->orderBy('name')->limit(self::TEAMS_PER_LEAGUE)])
            ->get(['id', 'league_id'])
            ->mapWithKeys(fn (Season $season): array => [
                $season->league_id => $season->teams
                    ->map(fn (Team $team): array => ['name' => $team->name, 'url' => $team->url()])
                    ->all(),
            ])
            ->all();
    }
}
