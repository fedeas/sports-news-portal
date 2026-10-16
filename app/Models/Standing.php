<?php

namespace App\Models;

use Database\Factories\StandingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A team's row in a season's league table. Maintained by RecalculateStandings; never edited by hand.
 *
 * @property int $id
 * @property int $season_id
 * @property int $team_id
 * @property int $played
 * @property int $won
 * @property int $drawn
 * @property int $lost
 * @property int $goals_for
 * @property int $goals_against
 * @property int $goal_difference
 * @property int $points
 * @property int|null $position
 * @property string|null $form Last results, oldest first: W, D or L per match
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'season_id', 'team_id', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against',
    'goal_difference', 'points', 'position', 'form',
])]
class Standing extends Model
{
    /** @use HasFactory<StandingFactory> */
    use HasFactory;

    public const CREATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'played' => 'integer',
            'won' => 'integer',
            'drawn' => 'integer',
            'lost' => 'integer',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
            'goal_difference' => 'integer',
            'points' => 'integer',
            'position' => 'integer',
        ];
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
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
