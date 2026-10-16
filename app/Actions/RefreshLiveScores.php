<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RefreshLiveScores
{
    public const CACHE_KEY = 'live-scores:v1';

    public const LIMIT = 40;

    /**
     * Rebuild the cached snapshot that every public live score widget reads.
     *
     * It holds matches in play plus every match of the current day in the display timezone,
     * live ones first. Visitors only ever read this cache; they never trigger database or
     * provider calls for live scores.
     */
    public function handle(): void
    {
        $timezone = config('app.display_timezone');
        $startOfDay = now($timezone)->startOfDay()->utc();
        $endOfDay = now($timezone)->endOfDay()->utc();

        $fixtures = Fixture::query()
            ->with(['homeTeam', 'awayTeam', 'season.league'])
            ->whereHas('season.league', fn (Builder $query) => $query->active())
            ->where(fn (Builder $query) => $query
                ->where('status', FixtureStatus::Live)
                ->orWhereBetween('kickoff_at', [$startOfDay, $endOfDay]))
            ->orderByRaw('case when status = ? then 0 else 1 end', [FixtureStatus::Live->value])
            ->orderBy('kickoff_at')
            ->limit(self::LIMIT)
            ->get();

        Cache::forever(self::CACHE_KEY, [
            'refreshed_at' => now()->toIso8601String(),
            'fixtures' => $fixtures->map(fn (Fixture $fixture) => self::snapshot($fixture))->all(),
        ]);
    }

    /**
     * The cache-friendly representation of a fixture used by the live score widgets.
     *
     * @return array{
     *     id: int, url: string, status: string, minute: int|null, kickoff_at: string,
     *     score_home: int|null, score_away: int|null, league: string,
     *     home: array{name: string, short_name: string|null, url: string},
     *     away: array{name: string, short_name: string|null, url: string},
     * }
     */
    public static function snapshot(Fixture $fixture): array
    {
        return [
            'id' => $fixture->id,
            'url' => route('fixtures.show', $fixture),
            'status' => $fixture->status->value,
            'minute' => $fixture->minute,
            'kickoff_at' => $fixture->kickoff_at->toIso8601String(),
            'score_home' => $fixture->score_home,
            'score_away' => $fixture->score_away,
            'league' => $fixture->season->league->name,
            'home' => ['name' => $fixture->homeTeam->name, 'short_name' => $fixture->homeTeam->short_name, 'url' => $fixture->homeTeam->url()],
            'away' => ['name' => $fixture->awayTeam->name, 'short_name' => $fixture->awayTeam->short_name, 'url' => $fixture->awayTeam->url()],
        ];
    }
}
