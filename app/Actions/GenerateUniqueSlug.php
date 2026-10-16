<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GenerateUniqueSlug
{
    public function __construct(private MakeSlug $makeSlug = new MakeSlug) {}

    /**
     * Build a URL slug from Greek or Latin text that is unique for the given model.
     *
     * Collisions (and reserved slugs) get a numeric suffix: `podosfairo`, `podosfairo-2`, `podosfairo-3`.
     * Soft-deleted records still count, because the unique index includes them.
     *
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $reserved
     * @param  array<int, class-string<Model>>  $alsoUniqueAgainst  other models sharing the same URL space
     */
    public function handle(
        string $source,
        string $model,
        ?int $ignoreId = null,
        int $maxLength = 120,
        array $reserved = [],
        array $alsoUniqueAgainst = [],
    ): string {
        $base = $this->makeSlug->handle($source);

        if ($base === '') {
            $base = 'untitled';
        }

        $candidate = $this->truncate($base, $maxLength);
        $suffix = 1;

        while ($this->isUnavailable($candidate, $model, $ignoreId, $reserved, $alsoUniqueAgainst)) {
            $suffix++;
            $candidate = $this->truncate($base, $maxLength - strlen("-{$suffix}"))."-{$suffix}";
        }

        return $candidate;
    }

    private function truncate(string $slug, int $length): string
    {
        return rtrim(substr($slug, 0, $length), '-');
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $reserved
     * @param  array<int, class-string<Model>>  $alsoUniqueAgainst
     */
    private function isUnavailable(string $slug, string $model, ?int $ignoreId, array $reserved, array $alsoUniqueAgainst): bool
    {
        if (in_array($slug, $reserved, true)) {
            return true;
        }

        $isTaken = $model::query()
            ->withoutGlobalScopes()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($isTaken) {
            return true;
        }

        foreach ($alsoUniqueAgainst as $otherModel) {
            if ($otherModel::query()->withoutGlobalScopes()->where('slug', $slug)->exists()) {
                return true;
            }
        }

        return false;
    }
}
