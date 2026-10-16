<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Author = 'author';

    /**
     * Get the translated, human-readable name of the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::Editor => __('Editor'),
            self::Author => __('Author'),
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isEditorOrAbove(): bool
    {
        return $this === self::Admin || $this === self::Editor;
    }
}
