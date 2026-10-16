<?php

namespace App\Actions;

use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Services\SportsData\Data\FixtureData;
use Illuminate\Support\Facades\Log;

class ImportFixtures
{
    public function __construct(private RecalculateStandings $recalculateStandings) {}

    /**
     * Create or update fixtures by external source and ID, then rebuild the standings once.
     *
     * Fixtures whose teams have not been imported are skipped. Model events are silenced during
     * the import so a large batch does not recalculate the standings once per fixture.
     *
     * @param  array<int, FixtureData>  $fixtures
     * @return int the number of fixtures created or updated
     */
    public function handle(string $source, array $fixtures, Season $season): int
    {
        $teamIds = Team::query()
            ->where('external_source', $source)
            ->whereIn('external_id', collect($fixtures)->flatMap(fn (FixtureData $data) => [$data->homeTeamExternalId, $data->awayTeamExternalId])->unique()->values())
            ->pluck('id', 'external_id');

        $imported = Fixture::withoutEvents(function () use ($source, $fixtures, $season, $teamIds) {
            $imported = 0;

            foreach ($fixtures as $data) {
                $homeTeamId = $teamIds[$data->homeTeamExternalId] ?? null;
                $awayTeamId = $teamIds[$data->awayTeamExternalId] ?? null;

                if ($homeTeamId === null || $awayTeamId === null || $homeTeamId === $awayTeamId) {
                    Log::warning('Skipped a fixture whose teams are unknown.', ['source' => $source, 'external_id' => $data->externalId]);

                    continue;
                }

                Fixture::query()->updateOrCreate(
                    ['external_source' => $source, 'external_id' => $data->externalId],
                    [
                        'season_id' => $season->id,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'kickoff_at' => $data->kickoffAt,
                        'status' => $data->status,
                        'score_home' => $data->scoreHome,
                        'score_away' => $data->scoreAway,
                        'minute' => $data->minute,
                        'round' => $data->round,
                        'venue' => $data->venue,
                    ],
                );

                $imported++;
            }

            return $imported;
        });

        $this->recalculateStandings->handle($season);

        return $imported;
    }
}
