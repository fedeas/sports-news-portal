<?php

namespace App\Actions;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class SearchTeams
{
    /**
     * Active clubs whose name contains the term, in alphabetical order.
     *
     * The term is matched literally: `%` and `_` typed by a visitor are not wildcards. The escape
     * character is `!`, which MySQL and SQLite both accept in an ESCAPE clause.
     *
     * @return Builder<Team>
     */
    public function handle(string $term): Builder
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        return Team::query()
            ->active()
            ->whereRaw("name like ? escape '!'", [$pattern])
            ->orderBy('name');
    }
}
