<?php

use App\Services\SportsData\ApiFootball\ApiFootballProvider;
use App\Services\SportsData\SportsDataManager;

it('has no provider when imports are disabled or the key is missing', function (?string $provider, ?string $key) {
    config(['sports-data.provider' => $provider, 'sports-data.api_football.key' => $key]);

    expect(app(SportsDataManager::class)->provider())->toBeNull();
})->with([
    'no provider' => [null, 'test-key'],
    'unknown provider' => ['another-provider', 'test-key'],
    'missing key' => ['api-football', null],
]);

it('builds the API-Football provider when it is configured', function () {
    config(['sports-data.provider' => 'api-football', 'sports-data.api_football.key' => 'test-key']);

    expect(app(SportsDataManager::class)->provider())->toBeInstanceOf(ApiFootballProvider::class);
});

it('reads competitions and ignores invalid entries', function () {
    config(['sports-data.competitions' => '318:2025, 197:2025,not-valid,12:25,']);

    expect(app(SportsDataManager::class)->competitions())->toBe([
        ['league' => '318', 'season' => '2025'],
        ['league' => '197', 'season' => '2025'],
    ]);
});
