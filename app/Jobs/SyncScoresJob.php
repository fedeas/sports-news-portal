<?php

namespace App\Jobs;

use App\Actions\ImportFixtures;
use App\Actions\RefreshLiveScores;
use App\Models\Season;
use App\Services\SportsData\SportsDataException;
use App\Services\SportsData\SportsDataManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncScoresJob implements ShouldQueue
{
    use Queueable;

    /**
     * The scheduler runs it again within minutes, so a failed run is not retried.
     */
    public int $tries = 1;

    /**
     * Refresh status, score and minute for fixtures from yesterday to tomorrow in each imported competition.
     *
     * Competitions not imported yet by SyncFixturesJob are skipped. Provider failures are logged, never thrown.
     */
    public function handle(SportsDataManager $sportsData, ImportFixtures $importFixtures, RefreshLiveScores $refreshLiveScores): void
    {
        $provider = $sportsData->provider();

        if ($provider === null) {
            return;
        }

        foreach ($sportsData->competitions() as $competition) {
            $season = Season::query()
                ->where('external_id', $competition['season'])
                ->whereHas('league', fn (Builder $query) => $query
                    ->where('external_source', $provider->source())
                    ->where('external_id', $competition['league']))
                ->first();

            if ($season === null) {
                Log::info('Live score sync skipped a competition that has not been imported yet.', $competition);

                continue;
            }

            try {
                $fixtures = $provider->fixtures($competition['league'], $competition['season'], now()->subDay(), now()->addDay());

                $importFixtures->handle($provider->source(), $fixtures, $season);
            } catch (SportsDataException $exception) {
                Log::warning('Live score sync failed for a competition.', [...$competition, 'error' => $exception->getMessage()]);
            }
        }

        // Fixture imports run without model events, so refresh the live score cache explicitly.
        $refreshLiveScores->handle();
    }
}
