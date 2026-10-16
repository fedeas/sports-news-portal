<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Services\ContentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListFixtures
{
    public const LIMIT = 5;

    /**
     * Fixtures still listed as upcoming this long after kick-off, so games in progress stay visible.
     */
    public const UPCOMING_GRACE_HOURS = 3;

    public function __construct(private ContentCache $contentCache) {}

    /**
     * The latest results and the next fixtures of a season or a team, with both teams' logos.
     *
     * Only the fixture ids are cached; the models are loaded fresh in a single query.
     *
     * @return array{recent: Collection<int, Fixture>, upcoming: Collection<int, Fixture>}
     */
    public function handle(Season|Team $subject): array
    {
        $key = $subject instanceof Season ? "season:{$subject->id}:fixtures:v1" : "team:{$subject->id}:fixtures:v1";

        ['recent' => $recentIds, 'upcoming' => $upcomingIds] = $this->contentCache->remember(
            [ContentCache::FIXTURES],
            $key,
            fn (): array => [
                'recent' => $this->query($subject)
                    ->where('status', FixtureStatus::Finished)
                    ->latest('kickoff_at')
                    ->limit(self::LIMIT)
                    ->pluck('id')
                    ->all(),
                'upcoming' => $this->query($subject)
                    ->whereIn('status', [FixtureStatus::Scheduled, FixtureStatus::Live])
                    ->where('kickoff_at', '>=', now()->subHours(self::UPCOMING_GRACE_HOURS))
                    ->oldest('kickoff_at')
                    ->limit(self::LIMIT)
                    ->pluck('id')
                    ->all(),
            ],
        );

        $fixtures = Fixture::query()
            ->whereKey([...$recentIds, ...$upcomingIds])
            ->with(['homeTeam.logo', 'awayTeam.logo'])
            ->get()
            ->keyBy('id');

        $pick = fn (array $ids): Collection => new Collection(
            collect($ids)->map(fn (int $id) => $fixtures->get($id))->filter()->values()->all(),
        );

        return ['recent' => $pick($recentIds), 'upcoming' => $pick($upcomingIds)];
    }

    /**
     * @return Builder<Fixture>
     */
    private function query(Season|Team $subject): Builder
    {
        return $subject instanceof Season
            ? Fixture::query()->where('season_id', $subject->id)
            : Fixture::query()->involving($subject);
    }
}
