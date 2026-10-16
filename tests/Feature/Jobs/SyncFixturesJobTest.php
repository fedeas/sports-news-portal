<?php

use App\Jobs\SyncFixturesJob;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sleep::fake();
    Http::preventStrayRequests();

    config([
        'sports-data.provider' => 'api-football',
        'sports-data.api_football.base_url' => 'https://api.test',
        'sports-data.api_football.key' => 'test-key',
        'sports-data.api_football.retries' => 0,
        'sports-data.competitions' => '318:2025',
        'sports-data.sport_slug' => 'podosfairo',
    ]);

    Sport::factory()->create(['slug' => 'podosfairo']);
});

/**
 * @return array<string, mixed>
 */
function providerPayload(string $name): array
{
    return json_decode(file_get_contents(base_path("tests/Fixtures/api-football/{$name}.json")), true);
}

it('imports the league, teams and fixtures of each configured competition', function () {
    Http::fake([
        'api.test/leagues*' => Http::response(providerPayload('leagues')),
        'api.test/teams*' => Http::response(providerPayload('teams')),
        'api.test/fixtures*' => Http::response(providerPayload('fixtures')),
    ]);

    SyncFixturesJob::dispatchSync();

    $league = League::query()->sole();

    expect($league->external_id)->toBe('318')
        ->and(Team::query()->count())->toBe(3)
        ->and(Fixture::query()->orderBy('external_id')->pluck('external_id')->all())->toBe(['9001', '9002', '9003'])
        ->and($league->seasons()->sole()->standings()->count())->toBe(3);
});

it('logs a provider failure without throwing', function () {
    Log::spy();
    Http::fake(['api.test/*' => Http::response([], 500)]);

    SyncFixturesJob::dispatchSync();

    expect(League::query()->count())->toBe(0);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'failed'))->once();
});

it('sends no requests when no provider is configured', function () {
    config(['sports-data.provider' => null]);

    SyncFixturesJob::dispatchSync();

    Http::assertNothingSent();
});
