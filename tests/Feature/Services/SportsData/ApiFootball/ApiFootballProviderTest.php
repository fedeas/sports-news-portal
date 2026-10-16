<?php

use App\Enums\FixtureStatus;
use App\Services\SportsData\ApiFootball\ApiFootballProvider;
use App\Services\SportsData\SportsDataException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();
    Http::preventStrayRequests();
});

function apiFootball(): ApiFootballProvider
{
    return new ApiFootballProvider('https://api.test', 'test-key', retries: 1, retrySleepMilliseconds: 0);
}

/**
 * @return array<string, mixed>
 */
function apiFootballPayload(string $name): array
{
    return json_decode(file_get_contents(base_path("tests/Fixtures/api-football/{$name}.json")), true);
}

it('reads a league with the requested season and sends the API key', function () {
    Http::fake(['api.test/leagues*' => Http::response(apiFootballPayload('leagues'))]);

    $league = apiFootball()->league('318', '2025');

    expect($league->externalId)->toBe('318')
        ->and($league->name)->toBe('Demo First Division')
        ->and($league->country)->toBe('CY')
        ->and($league->season->externalId)->toBe('2025')
        ->and($league->season->name)->toBe('2025/2026')
        ->and($league->season->startsOn->toDateString())->toBe('2025-08-22')
        ->and($league->season->isCurrent)->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->hasHeader('x-apisports-key', 'test-key')
        && $request['id'] === '318'
        && $request['season'] === '2025');
});

it('reads teams and skips malformed ones', function () {
    Http::fake(['api.test/teams*' => Http::response(apiFootballPayload('teams'))]);

    $teams = apiFootball()->teams('318', '2025');

    expect($teams)->toHaveCount(3)
        ->and($teams[0]->externalId)->toBe('101')
        ->and($teams[0]->name)->toBe('Demo Lions')
        ->and($teams[0]->shortName)->toBe('LIO')
        ->and($teams[0]->foundedYear)->toBe(1930)
        ->and($teams[2]->shortName)->toBeNull();
});

it('reads fixtures and skips malformed ones', function () {
    Http::fake(['api.test/fixtures*' => Http::response(apiFootballPayload('fixtures'))]);

    $fixtures = collect(apiFootball()->fixtures('318', '2025'))->keyBy('externalId');

    expect($fixtures->pluck('externalId')->values()->all())->toBe(['9001', '9002', '9003', '9005'])
        ->and($fixtures['9001']->status)->toBe(FixtureStatus::Finished)
        ->and($fixtures['9001']->scoreHome)->toBe(2)
        ->and($fixtures['9001']->scoreAway)->toBe(1)
        ->and($fixtures['9001']->kickoffAt->toDateTimeString())->toBe('2025-08-23 16:00:00')
        ->and($fixtures['9001']->round)->toBe('Regular Season - 1')
        ->and($fixtures['9001']->venue)->toBe('Demo Stadium')
        ->and($fixtures['9002']->status)->toBe(FixtureStatus::Scheduled)
        ->and($fixtures['9002']->scoreHome)->toBeNull()
        ->and($fixtures['9003']->status)->toBe(FixtureStatus::Live)
        ->and($fixtures['9003']->minute)->toBe(30);
});

it('maps provider status codes to fixture statuses', function (string $code, FixtureStatus $status) {
    $payload = apiFootballPayload('fixtures');
    $payload['response'] = [$payload['response'][0]];
    $payload['response'][0]['fixture']['status']['short'] = $code;
    Http::fake(['api.test/fixtures*' => Http::response($payload)]);

    expect(apiFootball()->fixtures('318', '2025')[0]->status)->toBe($status);
})->with([
    'not started' => ['NS', FixtureStatus::Scheduled],
    'half time' => ['HT', FixtureStatus::Live],
    'after extra time' => ['AET', FixtureStatus::Finished],
    'after penalties' => ['PEN', FixtureStatus::Finished],
    'postponed' => ['PST', FixtureStatus::Postponed],
    'abandoned' => ['ABD', FixtureStatus::Cancelled],
]);

it('asks for fixtures within a date range', function () {
    Http::fake(['api.test/fixtures*' => Http::response(['errors' => [], 'response' => []])]);

    apiFootball()->fixtures('318', '2025', CarbonImmutable::parse('2026-09-16'), CarbonImmutable::parse('2026-09-18'));

    Http::assertSent(fn (Request $request) => $request['from'] === '2026-09-16' && $request['to'] === '2026-09-18');
});

it('throws a sports data exception without leaking the key', function (mixed $response) {
    Http::fake(['api.test/*' => $response]);

    try {
        apiFootball()->league('318', '2025');
        $this->fail('Expected a SportsDataException.');
    } catch (SportsDataException $exception) {
        expect($exception->getMessage())->not->toContain('test-key');
    }
})->with([
    'server error' => fn () => Http::response([], 500),
    'provider error with HTTP 200' => fn () => Http::response(['errors' => ['token' => 'Error/Missing application key'], 'response' => []]),
    'unexpected payload' => fn () => Http::response(['errors' => [], 'response' => 'unexpected']),
    'league not found' => fn () => Http::response(['errors' => [], 'response' => []]),
]);

it('retries server errors', function () {
    Http::fake(['api.test/leagues*' => Http::sequence()->push([], 503)->push(apiFootballPayload('leagues'))]);

    expect(apiFootball()->league('318', '2025')->name)->toBe('Demo First Division');

    Http::assertSentCount(2);
});

it('does not retry client errors', function () {
    Http::fake(['api.test/leagues*' => Http::response([], 403)]);

    expect(fn () => apiFootball()->league('318', '2025'))->toThrow(SportsDataException::class);

    Http::assertSentCount(1);
});
