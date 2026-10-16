<?php

namespace App\Services\SportsData\Data;

final readonly class TeamData
{
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $shortName,
        public ?int $foundedYear,
    ) {}
}
