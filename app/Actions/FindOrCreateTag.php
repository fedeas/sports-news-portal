<?php

namespace App\Actions;

use App\Models\Tag;
use InvalidArgumentException;

class FindOrCreateTag
{
    public function __construct(private MakeSlug $makeSlug = new MakeSlug) {}

    /**
     * Return the tag matching the name, creating it when it doesn't exist yet.
     *
     * Tags are matched by slug, so names that differ only in case or accents
     * ("ΑΠΟΕΛ", "Αποέλ") resolve to the same tag.
     *
     * @throws InvalidArgumentException when the name has no letters or numbers
     */
    public function handle(string $name): Tag
    {
        $name = trim($name);
        $slug = $this->makeSlug->handle($name);

        if ($slug === '') {
            throw new InvalidArgumentException('A tag name must contain letters or numbers.');
        }

        return Tag::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
    }
}
