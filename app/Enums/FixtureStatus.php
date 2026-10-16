<?php

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Finished = 'finished';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    /**
     * Get the translated, human-readable name of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('Scheduled'),
            self::Live => __('Live'),
            self::Finished => __('Finished'),
            self::Postponed => __('Postponed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * Whether a fixture in this status has a score to show.
     */
    public function hasScore(): bool
    {
        return $this === self::Live || $this === self::Finished;
    }
}
