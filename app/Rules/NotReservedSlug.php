<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotReservedSlug implements ValidationRule
{
    /**
     * Fail when the slug collides with a top-level application route (config/reserved-slugs.php).
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (in_array(Str::lower($value), config('reserved-slugs'), true)) {
            $fail('The :attribute is reserved and cannot be used.')->translate();
        }
    }
}
