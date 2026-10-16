<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get the translated, human-readable name of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Scheduled => __('Scheduled'),
            self::Published => __('Published'),
            self::Archived => __('Archived'),
        };
    }
}
