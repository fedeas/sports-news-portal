<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class RecalculateStandings
{
    public const POINTS_FOR_WIN = 3;

    public const POINTS_FOR_DRAW = 1;

    public const FORM_LENGTH = 5;

    /**
     * Rebuild a season's league table from its finished fixtures and its roster.
     *
     * Teams are ranked by points, then goal difference, then goals scored, then name.
     * Roster teams without games get a zero row; rows for teams no longer involved are removed.
     */
    public function handle(Season $season): void
    {
        $fixtures = $season->fixtures()
            ->where('status', FixtureStatus::Finished)
            ->whereNotNull('score_home')
            ->whereNotNull('score_away')
            ->orderBy('kickoff_at')
            ->orderBy('id')
            ->get(['id', 'home_team_id', 'away_team_id', 'score_home', 'score_away']);

        $teamIds = $season->teams()->pluck('teams.id')
            ->merge($fixtures->pluck('home_team_id'))
            ->merge($fixtures->pluck('away_team_id'))
            ->unique()
            ->values();

        $rows = [];

        foreach ($teamIds as $teamId) {
            $rows[$teamId] = [
                'team_id' => $teamId, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
                'goals_for' => 0, 'goals_against' => 0, 'form' => '',
            ];
        }

        foreach ($fixtures as $fixture) {
            $this->recordResult($rows[$fixture->home_team_id], $fixture->score_home, $fixture->score_away);
            $this->recordResult($rows[$fixture->away_team_id], $fixture->score_away, $fixture->score_home);
        }

        $names = Team::query()->whereKey($teamIds)->pluck('name', 'id');

        $table = collect($rows)
            ->map(fn (array $row) => [
                ...$row,
                'goal_difference' => $row['goals_for'] - $row['goals_against'],
                'points' => $row['won'] * self::POINTS_FOR_WIN + $row['drawn'] * self::POINTS_FOR_DRAW,
                'form' => substr($row['form'], -self::FORM_LENGTH) ?: null,
            ])
            ->sort(fn (array $a, array $b) => [$b['points'], $b['goal_difference'], $b['goals_for'], $names[$a['team_id']] ?? '']
                <=> [$a['points'], $a['goal_difference'], $a['goals_for'], $names[$b['team_id']] ?? ''])
            ->values()
            ->map(fn (array $row, int $index) => [
                ...$row,
                'season_id' => $season->id,
                'position' => $index + 1,
                'updated_at' => now(),
            ]);

        DB::transaction(function () use ($season, $table, $teamIds) {
            $season->standings()->whereNotIn('team_id', $teamIds)->delete();

            if ($table->isNotEmpty()) {
                Standing::query()->upsert(
                    $table->all(),
                    ['season_id', 'team_id'],
                    ['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'goal_difference', 'points', 'position', 'form', 'updated_at'],
                );
            }
        });
    }

    /**
     * @param  array{team_id: int, played: int, won: int, drawn: int, lost: int, goals_for: int, goals_against: int, form: string}  $row
     */
    private function recordResult(array &$row, int $scored, int $conceded): void
    {
        $row['played']++;
        $row['goals_for'] += $scored;
        $row['goals_against'] += $conceded;

        if ($scored > $conceded) {
            $row['won']++;
            $row['form'] .= 'W';
        } elseif ($scored === $conceded) {
            $row['drawn']++;
            $row['form'] .= 'D';
        } else {
            $row['lost']++;
            $row['form'] .= 'L';
        }
    }
}
