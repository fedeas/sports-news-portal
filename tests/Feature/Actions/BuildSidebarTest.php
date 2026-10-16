<?php

use App\Actions\BuildSidebar;
use App\Models\Article;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:00:00'));
});

it('shows the current season of an active league', function () {
    $league = League::factory()->create(['name' => 'Πρωτάθλημα Α΄ Κατηγορίας']);
    Season::factory()->for($league)->create(['name' => '2024/2025']);
    $current = Season::factory()->current()->for($league)->create(['name' => '2025/2026']);
    $team = Team::factory()->create();
    $current->teams()->attach($team);
    Fixture::factory()->for($current)->finished(2, 0)->create(['home_team_id' => $team->id, 'kickoff_at' => now()->subDay()]);

    $sidebar = app(BuildSidebar::class)->handle();

    expect($sidebar['season']->is($current))->toBeTrue()
        ->and($sidebar['standings']->pluck('position')->all())->toBe([1, 2]);
});

it('leaves out the table of an inactive league', function () {
    Season::factory()->current()->for(League::factory()->inactive())->create();

    $sidebar = app(BuildSidebar::class)->handle();

    expect($sidebar['season'])->toBeNull()
        ->and($sidebar['standings'])->toBeEmpty()
        ->and($sidebar['upcoming'])->toBeEmpty();
});

it('lists the next four matches and the most read articles', function () {
    $season = Season::factory()->current()->create();
    Fixture::factory()->for($season)->count(6)->sequence(fn ($sequence) => ['kickoff_at' => now()->addDays($sequence->index + 1)])->create();
    Article::factory()->published()->create(['title' => 'Διαβασμένο', 'view_count' => 50]);
    Article::factory()->published()->create(['title' => 'Αδιάβαστο', 'view_count' => 0]);

    $sidebar = app(BuildSidebar::class)->handle();

    expect($sidebar['upcoming'])->toHaveCount(BuildSidebar::FIXTURES)
        ->and($sidebar['mostRead']->pluck('title')->all())->toBe(['Διαβασμένο']);
});
