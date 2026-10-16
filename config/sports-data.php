<?php

/*
|--------------------------------------------------------------------------
| Sports Data
|--------------------------------------------------------------------------
|
| External provider for teams, leagues, fixtures and live scores. Every call
| goes through App\Services\SportsData. Nothing is imported until a provider
| and its API key are configured. Keys live only in the environment.
|
*/

return [

    // Provider driver: "api-football", or null to disable all imports.
    'provider' => env('SPORTS_DATA_PROVIDER'),

    'api_football' => [
        'base_url' => env('API_FOOTBALL_BASE_URL', 'https://v3.football.api-sports.io'),
        'key' => env('API_FOOTBALL_KEY'),
        'timeout' => (int) env('API_FOOTBALL_TIMEOUT', 10),
        'retries' => (int) env('API_FOOTBALL_RETRIES', 2),
        'retry_sleep_milliseconds' => 500,
    ],

    // Slug of the local sport imported leagues and teams belong to.
    'sport_slug' => env('SPORTS_DATA_SPORT', 'podosfairo'),

    // Competitions to import, as comma-separated "providerLeagueId:season" pairs, e.g. "318:2025".
    'competitions' => env('SPORTS_DATA_COMPETITIONS', ''),

    // How often live scores are synced, in minutes. Mind the provider's daily request quota.
    'scores_interval_minutes' => (int) env('SPORTS_DATA_SCORES_INTERVAL', 5),

];
