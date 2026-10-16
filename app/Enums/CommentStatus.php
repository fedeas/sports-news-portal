<?php

namespace App\Enums;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';
    case Rejected = 'rejected';

    /**
     * Get the translated, human-readable name of the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Approved => __('Approved'),
            self::Spam => __('Spam'),
            self::Rejected => __('Rejected'),
        };
    }
}
