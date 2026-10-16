<?php

namespace App\Services\SportsData;

use App\Services\SportsData\ApiFootball\ApiFootballProvider;

class SportsDataManager
{
    /**
     * The configured provider, or null when imports are disabled or its credentials are missing.
     */
    public function provider(): ?SportsDataProvider
    {
        return match (config('sports-data.provider')) {
            ApiFootballProvider::SOURCE => filled(config('sports-data.api_football.key'))
                ? new ApiFootballProvider(
                    baseUrl: (string) config('sports-data.api_football.base_url'),
                    apiKey: (string) config('sports-data.api_football.key'),
                    timeout: (int) config('sports-data.api_football.timeout'),
                    retries: (int) config('sports-data.api_football.retries'),
                    retrySleepMilliseconds: (int) config('sports-data.api_football.retry_sleep_milliseconds'),
                )
                : null,
            default => null,
        };
    }

    /**
     * The competitions to import, parsed from "leagueId:season" pairs. Invalid pairs are ignored.
     *
     * @return array<int, array{league: string, season: string}>
     */
    public function competitions(): array
    {
        return collect(explode(',', (string) config('sports-data.competitions')))
            ->map(fn (string $pair) => trim($pair))
            ->filter(fn (string $pair) => preg_match('/^\d+:\d{4}$/', $pair) === 1)
            ->map(function (string $pair) {
                [$league, $season] = explode(':', $pair);

                return ['league' => $league, 'season' => $season];
            })
            ->values()
            ->all();
    }
}
