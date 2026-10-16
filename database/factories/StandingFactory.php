<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Standing>
 */
class StandingFactory extends Factory
{
    /**
     * Define the model's default state: a team that has not played yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'team_id' => Team::factory(),
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
            'position' => null,
            'form' => null,
        ];
    }
}
