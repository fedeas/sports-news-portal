<?php

namespace App\Enums;

/**
 * The black-and-white pictograms a section can carry in the menu and on its page.
 * Each one is drawn in resources/views/components/section-icon.blade.php.
 */
enum SectionIcon: string
{
    case Football = 'football';
    case Basketball = 'basketball';
    case Volleyball = 'volleyball';
    case Tennis = 'tennis';
    case Athletics = 'athletics';
    case CombatSports = 'combat-sports';
    case OtherSports = 'other-sports';
    case Transfers = 'transfers';
    case NationalTeams = 'national-teams';
    case Opinion = 'opinion';
    case News = 'news';

    public function label(): string
    {
        return match ($this) {
            self::Football => __('Football'),
            self::Basketball => __('Basketball'),
            self::Volleyball => __('Volleyball'),
            self::Tennis => __('Tennis'),
            self::Athletics => __('Athletics'),
            self::CombatSports => __('Combat sports'),
            self::OtherSports => __('Other sports'),
            self::Transfers => __('Transfers'),
            self::NationalTeams => __('National teams'),
            self::Opinion => __('Opinion'),
            self::News => __('News'),
        };
    }
}
