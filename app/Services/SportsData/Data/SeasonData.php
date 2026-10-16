<?php

namespace App\Services\SportsData\Data;

use Carbon\CarbonImmutable;

final readonly class SeasonData
{
    /**
     * @param  string  $externalId  the provider's season key, e.g. "2025"
     * @param  string  $name  the local season name, e.g. "2025/2026"
     */
    public function __construct(
        public string $externalId,
        public string $name,
        public ?CarbonImmutable $startsOn,
        public ?CarbonImmutable $endsOn,
        public bool $isCurrent,
    ) {}
}
