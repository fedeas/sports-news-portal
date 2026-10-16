<?php

namespace App\Jobs;

use App\Actions\ImportFixtures;
use App\Actions\ImportLeague;
use App\Actions\ImportTeams;
use App\Models\Sport;
use App\Services\SportsData\SportsDataException;
use App\Services\SportsData\SportsDataManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncFixturesJob implements ShouldQueue
{
    use Queueable;

    /**
     * The scheduler runs it again tomorrow, so a failed run is not retried.
     */
    public int $tries = 1;

    /**
     * Import each configured competition's league, season, teams and full fixture list.
     *
     * A provider failure for one competition is logged and the others still run; nothing is
     * thrown back to the scheduler.
     */
    public function handle(SportsDataManager $sportsData, ImportLeague $importLeague, ImportTeams $importTeams, ImportFixtures $importFixtures): void
    {
        $provider = $sportsData->provider();

        if ($provider === null) {
            Log::info('Sports data sync skipped: no provider is configured.');

            return;
        }

        $sport = Sport::query()->where('slug', config('sports-data.sport_slug'))->first();

        if ($sport === null) {
            Log::warning('Sports data sync skipped: the configured sport does not exist.', ['sport' => config('sports-data.sport_slug')]);

            return;
        }

        foreach ($sportsData->competitions() as $competition) {
            try {
                [, $season] = $importLeague->handle($provider->source(), $provider->league($competition['league'], $competition['season']), $sport);

                $importTeams->handle($provider->source(), $provider->teams($competition['league'], $competition['season']), $season);

                $importFixtures->handle($provider->source(), $provider->fixtures($competition['league'], $competition['season']), $season);
            } catch (SportsDataException $exception) {
                Log::warning('Sports data sync failed for a competition.', [...$competition, 'error' => $exception->getMessage()]);
            }
        }
    }
}
