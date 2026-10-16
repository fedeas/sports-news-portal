<?php

namespace App\Enums;

enum MediaCollection: string
{
    case Default = 'default';
    case FeaturedImage = 'featured_image';
    case OgImage = 'og_image';
    case Logo = 'logo';
    case Gallery = 'gallery';
    case Avatar = 'avatar';

    /**
     * Whether images in this collection must describe their content for screen readers.
     */
    public function requiresAltText(): bool
    {
        return $this === self::FeaturedImage || $this === self::Gallery;
    }
}
