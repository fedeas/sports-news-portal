<?php

namespace App\Services\SportsData\Data;

use App\Enums\FixtureStatus;
use Carbon\CarbonImmutable;

final readonly class FixtureData
{
    /**
     * @param  CarbonImmutable  $kickoffAt  UTC
     */
    public function __construct(
        public string $externalId,
        public string $homeTeamExternalId,
        public string $awayTeamExternalId,
        public CarbonImmutable $kickoffAt,
        public FixtureStatus $status,
        public ?int $scoreHome,
        public ?int $scoreAway,
        public ?int $minute,
        public ?string $round,
        public ?string $venue,
    ) {}
}
