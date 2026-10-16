<?php

namespace App\Models;

use App\Actions\RecalculateStandings;
use App\Actions\RefreshLiveScores;
use App\Concerns\FlushesContentCache;
use App\Enums\FixtureStatus;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property CarbonImmutable $kickoff_at UTC
 * @property FixtureStatus $status
 * @property int|null $score_home
 * @property int|null $score_away
 * @property int|null $minute
 * @property string|null $round
 * @property string|null $venue
 * @property string|null $external_source
 * @property string|null $external_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'season_id', 'home_team_id', 'away_team_id', 'kickoff_at', 'status', 'score_home', 'score_away',
    'minute', 'round', 'venue', 'external_source', 'external_id',
])]
class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use FlushesContentCache, HasFactory;

    /**
     * @return array<int, string>
     */
    protected static function contentCacheTags(): array
    {
        return [ContentCache::FIXTURES];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'status' => FixtureStatus::class,
            'score_home' => 'integer',
            'score_away' => 'integer',
            'minute' => 'integer',
        ];
    }

    /**
     * Keep the live score cache and the standings in step with results, whoever saves the fixture.
     */
    protected static function booted(): void
    {
        static::saved(function (Fixture $fixture) {
            app(RefreshLiveScores::class)->handle();

            if (! $fixture->affectsStandings()) {
                return;
            }

            $recalculate = app(RecalculateStandings::class);

            // Look the season up by id: a cached `season` relation may still point at the old one.
            if ($season = Season::query()->find($fixture->season_id)) {
                $recalculate->handle($season);
            }

            if ($fixture->wasChanged('season_id') && $previousSeason = Season::query()->whereKey($fixture->getOriginal('season_id'))->first()) {
                $recalculate->handle($previousSeason);
            }
        });

        static::deleted(function (Fixture $fixture) {
            app(RefreshLiveScores::class)->handle();

            if ($fixture->status === FixtureStatus::Finished && $season = Season::query()->find($fixture->season_id)) {
                app(RecalculateStandings::class)->handle($season);
            }
        });
    }

    /**
     * Fixtures a team plays in, home or away.
     *
     * @param  Builder<Fixture>  $query
     */
    #[Scope]
    protected function involving(Builder $query, Team $team): void
    {
        $query->where(fn (Builder $query) => $query->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id));
    }

    /**
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Whether the last save changed a finished result, or finished/unfinished the fixture.
     */
    private function affectsStandings(): bool
    {
        $isFinished = $this->status === FixtureStatus::Finished;
        $wasFinished = ! $this->wasRecentlyCreated && $this->getOriginal('status') === FixtureStatus::Finished;

        if (! $isFinished && ! $wasFinished) {
            return false;
        }

        return $this->wasRecentlyCreated
            || $this->wasChanged(['status', 'score_home', 'score_away', 'home_team_id', 'away_team_id', 'season_id']);
    }
}
