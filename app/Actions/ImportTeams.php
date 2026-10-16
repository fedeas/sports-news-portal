<?php

namespace App\Actions;

use App\Models\Season;
use App\Models\Team;
use App\Services\SportsData\Data\TeamData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ImportTeams
{
    public function __construct(private GenerateUniqueSlug $generateUniqueSlug) {}

    /**
     * Create teams seen for the first time and add every team to the season's roster.
     *
     * Existing teams are matched by external source and ID and keep their editorial fields.
     *
     * @param  array<int, TeamData>  $teams
     * @return Collection<int, Team>
     */
    public function handle(string $source, array $teams, Season $season): Collection
    {
        return DB::transaction(function () use ($source, $teams, $season) {
            $sportId = $season->league()->value('sport_id');

            $imported = new Collection(array_map(function (TeamData $data) use ($source, $sportId) {
                $team = Team::query()->firstOrNew(['external_source' => $source, 'external_id' => $data->externalId]);

                if (! $team->exists) {
                    $team->fill([
                        'sport_id' => $sportId,
                        'name' => $data->name,
                        'slug' => $this->generateUniqueSlug->handle($data->name, Team::class, maxLength: 120),
                        'short_name' => $data->shortName,
                        'founded_year' => $data->foundedYear,
                        'is_active' => true,
                    ])->save();
                }

                return $team;
            }, $teams));

            $season->teams()->syncWithoutDetaching($imported->modelKeys());

            return $imported;
        });
    }
}
