<?php

namespace App\Services\SportsData\Data;

final readonly class LeagueData
{
    /**
     * @param  string|null  $country  ISO 3166-1 alpha-2 code
     */
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $country,
        public SeasonData $season,
    ) {}
}
