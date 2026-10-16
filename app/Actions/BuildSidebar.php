<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Standing;
use Illuminate\Database\Eloquent\Collection;

class BuildSidebar
{
    public const STANDINGS_ROWS = 6;

    public const FIXTURES = 4;

    public function __construct(
        private BuildHomepage $buildHomepage,
        private ListFixtures $listFixtures,
    ) {}

    /**
     * The rail that runs beside the news: the current league table, the next matches and the most read.
     *
     * @return array{season: ?Season, standings: Collection<int, Standing>, upcoming: Collection<int, Fixture>, mostRead: Collection<int, Article>}
     */
    public function handle(): array
    {
        $season = Season::query()
            ->where('is_current', true)
            ->whereHas('league', fn ($query) => $query->active())
            ->with('league')
            ->orderBy('id')
            ->first();

        return [
            'season' => $season,
            'standings' => $season === null
                ? new Collection
                : $season->standings()->with('team.logo')->orderBy('position')->limit(self::STANDINGS_ROWS)->get(),
            'upcoming' => $season === null
                ? new Collection
                : $this->listFixtures->handle($season)['upcoming']->take(self::FIXTURES),
            'mostRead' => $this->buildHomepage->mostRead(),
        ];
    }
}
