<?php

namespace App\Services\SportsData\ApiFootball;

use App\Enums\FixtureStatus;
use App\Services\SportsData\Data\FixtureData;
use App\Services\SportsData\Data\LeagueData;
use App\Services\SportsData\Data\SeasonData;
use App\Services\SportsData\Data\TeamData;
use App\Services\SportsData\SportsDataException;
use App\Services\SportsData\SportsDataProvider;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * API-Football v3 (api-sports.io).
 */
final class ApiFootballProvider implements SportsDataProvider
{
    public const SOURCE = 'api-football';

    /**
     * Provider status codes and the local fixture status they correspond to.
     */
    private const STATUSES = [
        'TBD' => FixtureStatus::Scheduled,
        'NS' => FixtureStatus::Scheduled,
        '1H' => FixtureStatus::Live,
        'HT' => FixtureStatus::Live,
        '2H' => FixtureStatus::Live,
        'ET' => FixtureStatus::Live,
        'BT' => FixtureStatus::Live,
        'P' => FixtureStatus::Live,
        'SUSP' => FixtureStatus::Live,
        'INT' => FixtureStatus::Live,
        'LIVE' => FixtureStatus::Live,
        'FT' => FixtureStatus::Finished,
        'AET' => FixtureStatus::Finished,
        'PEN' => FixtureStatus::Finished,
        'AWD' => FixtureStatus::Finished,
        'WO' => FixtureStatus::Finished,
        'PST' => FixtureStatus::Postponed,
        'CANC' => FixtureStatus::Cancelled,
        'ABD' => FixtureStatus::Cancelled,
    ];

    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private int $timeout = 10,
        private int $retries = 2,
        private int $retrySleepMilliseconds = 500,
    ) {}

    public function source(): string
    {
        return self::SOURCE;
    }

    public function league(string $leagueId, string $season): LeagueData
    {
        $item = $this->get('leagues', ['id' => $leagueId, 'season' => $season])[0] ?? null;

        $name = Arr::get((array) $item, 'league.name');
        $seasonItem = collect(Arr::get((array) $item, 'seasons', []))
            ->first(fn (mixed $candidate) => is_array($candidate) && (string) ($candidate['year'] ?? '') === $season);

        if (! is_string($name) || $name === '' || ! is_array($seasonItem)) {
            throw new SportsDataException("League {$leagueId} ({$season}) was not found or is incomplete.");
        }

        $countryCode = Arr::get($item, 'country.code');

        return new LeagueData(
            externalId: $leagueId,
            name: Str::limit($name, 100, ''),
            country: is_string($countryCode) && preg_match('/^[A-Z]{2}$/', $countryCode) ? $countryCode : null,
            season: new SeasonData(
                externalId: $season,
                name: $season.'/'.((int) $season + 1),
                startsOn: $this->date($seasonItem['start'] ?? null),
                endsOn: $this->date($seasonItem['end'] ?? null),
                isCurrent: ($seasonItem['current'] ?? false) === true,
            ),
        );
    }

    public function teams(string $leagueId, string $season): array
    {
        $teams = [];

        foreach ($this->get('teams', ['league' => $leagueId, 'season' => $season]) as $index => $item) {
            $id = Arr::get((array) $item, 'team.id');
            $name = Arr::get((array) $item, 'team.name');

            if (! is_numeric($id) || ! is_string($name) || $name === '') {
                Log::warning('Skipped a malformed team from the sports data provider.', ['source' => self::SOURCE, 'index' => $index]);

                continue;
            }

            $code = Arr::get($item, 'team.code');
            $founded = Arr::get($item, 'team.founded');

            $teams[] = new TeamData(
                externalId: (string) $id,
                name: Str::limit($name, 100, ''),
                shortName: is_string($code) && $code !== '' ? Str::limit($code, 20, '') : null,
                foundedYear: is_int($founded) ? $founded : null,
            );
        }

        return $teams;
    }

    public function fixtures(string $leagueId, string $season, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $query = array_filter([
            'league' => $leagueId,
            'season' => $season,
            'from' => $from?->utc()->toDateString(),
            'to' => $to?->utc()->toDateString(),
            'timezone' => 'UTC',
        ]);

        $fixtures = [];

        foreach ($this->get('fixtures', $query) as $index => $item) {
            $fixture = $this->fixture((array) $item);

            if ($fixture === null) {
                Log::warning('Skipped a malformed fixture from the sports data provider.', ['source' => self::SOURCE, 'index' => $index]);

                continue;
            }

            $fixtures[] = $fixture;
        }

        return $fixtures;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function fixture(array $item): ?FixtureData
    {
        $id = Arr::get($item, 'fixture.id');
        $homeId = Arr::get($item, 'teams.home.id');
        $awayId = Arr::get($item, 'teams.away.id');
        $status = self::STATUSES[Arr::get($item, 'fixture.status.short')] ?? null;
        $kickoffAt = $this->dateTime(Arr::get($item, 'fixture.date'));

        if (! is_numeric($id) || ! is_numeric($homeId) || ! is_numeric($awayId) || $status === null || $kickoffAt === null) {
            return null;
        }

        $round = Arr::get($item, 'league.round');
        $venue = Arr::get($item, 'fixture.venue.name');

        return new FixtureData(
            externalId: (string) $id,
            homeTeamExternalId: (string) $homeId,
            awayTeamExternalId: (string) $awayId,
            kickoffAt: $kickoffAt,
            status: $status,
            scoreHome: $this->score(Arr::get($item, 'goals.home')),
            scoreAway: $this->score(Arr::get($item, 'goals.away')),
            minute: is_int($elapsed = Arr::get($item, 'fixture.status.elapsed')) ? $elapsed : null,
            round: is_string($round) ? Str::limit($round, 30, '') : null,
            venue: is_string($venue) ? Str::limit($venue, 150, '') : null,
        );
    }

    /**
     * Call an endpoint and return its `response` list.
     *
     * @param  array<string, string>  $query
     * @return array<int, mixed>
     *
     * @throws SportsDataException
     */
    private function get(string $endpoint, array $query): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['x-apisports-key' => $this->apiKey])
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(
                    $this->retries + 1,
                    $this->retrySleepMilliseconds,
                    fn (Throwable $exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false,
                )
                ->get($endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new SportsDataException("Could not reach the sports data provider ({$endpoint}).", previous: $exception);
        }

        if ($response->failed()) {
            throw new SportsDataException("The sports data provider returned HTTP {$response->status()} for {$endpoint}.");
        }

        // The provider reports problems (quota, bad key, bad parameters) with HTTP 200 and an `errors` field.
        if (! empty($response->json('errors'))) {
            throw new SportsDataException("The sports data provider reported an error for {$endpoint}.");
        }

        $items = $response->json('response');

        if (! is_array($items) || ! array_is_list($items)) {
            throw new SportsDataException("Unexpected response from the sports data provider for {$endpoint}.");
        }

        return $items;
    }

    private function score(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 && $value <= 99 ? $value : null;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? CarbonImmutable::parse($value, 'UTC') : null;
    }

    private function dateTime(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }
}
