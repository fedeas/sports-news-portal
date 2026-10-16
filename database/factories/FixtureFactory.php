<?php

namespace Database\Factories;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    /**
     * Define the model's default state: an upcoming match.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'kickoff_at' => now()->addDays(fake()->numberBetween(1, 30))->setTime(19, 0),
            'status' => FixtureStatus::Scheduled,
            'score_home' => null,
            'score_away' => null,
            'minute' => null,
            'round' => (string) fake()->numberBetween(1, 30),
            'venue' => null,
            'external_source' => null,
            'external_id' => null,
        ];
    }

    /**
     * Indicate that the match is over with the given score.
     */
    public function finished(int $homeScore = 1, int $awayScore = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixtureStatus::Finished,
            'kickoff_at' => now()->subDays(fake()->numberBetween(1, 30))->setTime(19, 0),
            'score_home' => $homeScore,
            'score_away' => $awayScore,
        ]);
    }

    /**
     * Indicate that the match is being played right now.
     */
    public function live(int $homeScore = 0, int $awayScore = 0, int $minute = 55): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FixtureStatus::Live,
            'kickoff_at' => now()->subMinutes($minute),
            'score_home' => $homeScore,
            'score_away' => $awayScore,
            'minute' => $minute,
        ]);
    }
}
