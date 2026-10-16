<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Locale;

/**
 * Show a model's ISO 3166-1 alpha-2 `country` code as a country name in the current locale.
 *
 * @mixin Model
 */
trait HasCountry
{
    /**
     * The country name, e.g. "Κύπρος" for CY; the bare code when the intl extension is missing.
     */
    public function countryName(): ?string
    {
        if ($this->country === null) {
            return null;
        }

        if (! extension_loaded('intl')) {
            return $this->country;
        }

        $locale = app()->getLocale();
        $name = Locale::getDisplayRegion('-'.$this->country, $locale);

        // ICU names every code it does not know after the reserved "ZZ" (Unknown Region).
        $unknown = Locale::getDisplayRegion('-ZZ', $locale);

        return $name !== false && $name !== '' && $name !== $unknown ? $name : $this->country;
    }
}
