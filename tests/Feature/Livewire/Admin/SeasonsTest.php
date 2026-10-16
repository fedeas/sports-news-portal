<?php

use App\Livewire\Admin\Seasons;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.seasons.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.seasons.index'))
            ->assertForbidden();
    });
});

describe('saving seasons', function () {
    it('creates a season for a league', function () {
        $this->actingAs(User::factory()->editor()->create());
        $league = League::factory()->create();

        Livewire::test(Seasons::class)
            ->call('create')
            ->set('league_id', (string) $league->id)
            ->set('name', '2025/2026')
            ->set('starts_on', '2025-08-23')
            ->set('ends_on', '2026-05-17')
            ->call('save')
            ->assertHasNoErrors();

        $season = Season::query()->sole();

        expect($season->league_id)->toBe($league->id)
            ->and($season->starts_on->toDateString())->toBe('2025-08-23');
    });

    it('rejects a name that the league already uses', function () {
        $this->actingAs(User::factory()->editor()->create());
        $season = Season::factory()->create(['name' => '2025/2026']);

        Livewire::test(Seasons::class)
            ->call('create')
            ->set('league_id', (string) $season->league_id)
            ->set('name', '2025/2026')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    });

    it('accepts a name another league already uses', function () {
        $this->actingAs(User::factory()->editor()->create());
        Season::factory()->create(['name' => '2025/2026']);

        Livewire::test(Seasons::class)
            ->call('create')
            ->set('league_id', (string) League::factory()->create()->id)
            ->set('name', '2025/2026')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('rejects names that are not a year or a year range', function (string $name) {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Seasons::class)
            ->call('create')
            ->set('league_id', (string) League::factory()->create()->id)
            ->set('name', $name)
            ->call('save')
            ->assertHasErrors(['name' => 'regex']);
    })->with(['2025-26', 'Σεζόν 2025']);

    it('rejects an end date before the start date', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Seasons::class)
            ->call('create')
            ->set('league_id', (string) League::factory()->create()->id)
            ->set('name', '2025/2026')
            ->set('starts_on', '2025-08-23')
            ->set('ends_on', '2025-08-01')
            ->call('save')
            ->assertHasErrors(['ends_on' => 'after_or_equal']);
    });

    it('unmarks the league\'s other seasons when one becomes current', function () {
        $this->actingAs(User::factory()->editor()->create());
        $league = League::factory()->create();
        $previous = Season::factory()->current()->for($league)->create(['name' => '2024/2025']);
        $otherLeagueCurrent = Season::factory()->current()->create();
        $next = Season::factory()->for($league)->create(['name' => '2025/2026']);

        Livewire::test(Seasons::class)
            ->call('edit', $next->id)
            ->set('is_current', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($next->refresh()->is_current)->toBeTrue()
            ->and($previous->refresh()->is_current)->toBeFalse()
            ->and($otherLeagueCurrent->refresh()->is_current)->toBeTrue();
    });
});

it('filters seasons by league', function () {
    $this->actingAs(User::factory()->editor()->create());
    $shown = Season::factory()->create();
    Season::factory()->create();

    $component = Livewire::test(Seasons::class)->set('league', (string) $shown->league_id);

    expect($component->instance()->seasons->modelKeys())->toBe([$shown->id]);
});

describe('rosters', function () {
    it('saves the teams of a season and gives each a standings row', function () {
        $this->actingAs(User::factory()->editor()->create());
        $season = Season::factory()->create();
        $teams = Team::factory()->count(2)->create(['sport_id' => $season->league->sport_id]);

        Livewire::test(Seasons::class)
            ->call('editRoster', $season->id)
            ->set('roster_team_ids', $teams->map(fn (Team $team) => (string) $team->id)->all())
            ->call('saveRoster')
            ->assertHasNoErrors();

        expect($season->teams()->count())->toBe(2)
            ->and($season->standings()->count())->toBe(2);
    });

    it('rejects teams from another sport', function () {
        $this->actingAs(User::factory()->editor()->create());
        $season = Season::factory()->create();

        Livewire::test(Seasons::class)
            ->call('editRoster', $season->id)
            ->set('roster_team_ids', [(string) Team::factory()->create()->id])
            ->call('saveRoster')
            ->assertHasErrors(['roster_team_ids.0' => 'exists']);

        expect($season->teams()->count())->toBe(0);
    });
});

it('keeps a season that still has fixtures', function () {
    $this->actingAs(User::factory()->editor()->create());
    $fixture = Fixture::factory()->create();

    Livewire::test(Seasons::class)->call('delete', $fixture->season_id);

    expect(Season::query()->find($fixture->season_id))->not->toBeNull();
});

it('deletes a season', function () {
    $this->actingAs(User::factory()->editor()->create());
    $season = Season::factory()->create();

    Livewire::test(Seasons::class)->call('delete', $season->id);

    expect(Season::query()->find($season->id))->toBeNull();
});
