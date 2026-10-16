<?php

namespace App\Actions;

use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Services\SportsData\Data\LeagueData;
use Illuminate\Support\Facades\DB;

class ImportLeague
{
    public function __construct(private GenerateUniqueSlug $generateUniqueSlug) {}

    /**
     * Create the league on first import and create or update the season.
     *
     * Existing leagues are matched by external source and ID and keep their editorial fields
     * (Greek name, slug, logo, active flag), which editors may have changed.
     *
     * @return array{0: League, 1: Season}
     */
    public function handle(string $source, LeagueData $data, Sport $sport): array
    {
        return DB::transaction(function () use ($source, $data, $sport) {
            $league = League::query()->firstOrNew(['external_source' => $source, 'external_id' => $data->externalId]);

            if (! $league->exists) {
                $league->fill([
                    'sport_id' => $sport->id,
                    'name' => $data->name,
                    'slug' => $this->generateUniqueSlug->handle($data->name, League::class, maxLength: 120),
                    'country' => $data->country,
                    'is_active' => true,
                ])->save();
            }

            $season = Season::query()->firstOrNew(['league_id' => $league->id, 'name' => $data->season->name]);

            $season->fill([
                'starts_on' => $data->season->startsOn,
                'ends_on' => $data->season->endsOn,
                'external_id' => $data->season->externalId,
                'is_current' => $data->season->isCurrent,
            ])->save();

            if ($season->is_current) {
                Season::query()->where('league_id', $league->id)->whereKeyNot($season->id)->update(['is_current' => false]);
            }

            return [$league, $season];
        });
    }
}
