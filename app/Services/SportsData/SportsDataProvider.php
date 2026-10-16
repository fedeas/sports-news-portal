<?php

namespace App\Services\SportsData;

use App\Services\SportsData\Data\FixtureData;
use App\Services\SportsData\Data\LeagueData;
use App\Services\SportsData\Data\TeamData;
use Carbon\CarbonInterface;

/**
 * A licensed external source of leagues, teams and fixtures.
 *
 * Implementations translate the provider's payloads into the plain data objects below, so the
 * import actions never depend on a specific provider.
 */
interface SportsDataProvider
{
    /**
     * The value stored in `external_source` for records from this provider.
     */
    public function source(): string;

    /**
     * @throws SportsDataException
     */
    public function league(string $leagueId, string $season): LeagueData;

    /**
     * @return array<int, TeamData>
     *
     * @throws SportsDataException
     */
    public function teams(string $leagueId, string $season): array;

    /**
     * @return array<int, FixtureData>
     *
     * @throws SportsDataException
     */
    public function fixtures(string $leagueId, string $season, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array;
}
