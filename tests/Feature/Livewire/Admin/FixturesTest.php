<?php

use App\Enums\FixtureStatus;
use App\Livewire\Admin\Fixtures;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function fillFixtureForm(Testable $component, Season $season, Team $home, Team $away): Testable
{
    return $component
        ->call('create')
        ->set('season_id', (string) $season->id)
        ->set('home_team_id', (string) $home->id)
        ->set('away_team_id', (string) $away->id)
        ->set('kickoff_at', '2026-09-20T19:00');
}

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.fixtures.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.fixtures.index'))
            ->assertForbidden();
    });

    it('renders fixtures for editors', function () {
        Fixture::factory()->create(['home_team_id' => Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας'])->id]);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.fixtures.index'))
            ->assertOk()
            ->assertSee('Ολυμπιάδα Λευκωσίας');
    });
});

describe('saving fixtures', function () {
    it('creates a fixture with the kickoff entered in Cyprus time stored in UTC', function () {
        $this->actingAs(User::factory()->editor()->create());
        $season = Season::factory()->create();
        [$home, $away] = Team::factory()->count(2)->create();

        fillFixtureForm(Livewire::test(Fixtures::class), $season, $home, $away)
            ->set('round', '1')
            ->call('save')
            ->assertHasNoErrors();

        $fixture = Fixture::query()->sole();

        expect($fixture->kickoff_at->toDateTimeString())->toBe('2026-09-20 16:00:00')
            ->and($fixture->status)->toBe(FixtureStatus::Scheduled)
            ->and($fixture->score_home)->toBeNull();
    });

    it('rejects a team playing against itself', function () {
        $this->actingAs(User::factory()->editor()->create());
        $team = Team::factory()->create();

        fillFixtureForm(Livewire::test(Fixtures::class), Season::factory()->create(), $team, $team)
            ->call('save')
            ->assertHasErrors(['away_team_id' => 'different']);

        expect(Fixture::query()->count())->toBe(0);
    });

    it('requires a score once the match has started', function (string $status) {
        $this->actingAs(User::factory()->editor()->create());
        [$home, $away] = Team::factory()->count(2)->create();

        fillFixtureForm(Livewire::test(Fixtures::class), Season::factory()->create(), $home, $away)
            ->set('fixture_status', $status)
            ->call('save')
            ->assertHasErrors(['score_home' => 'required', 'score_away' => 'required']);
    })->with(['live', 'finished']);

    it('rejects an external ID already used by the same source', function () {
        $this->actingAs(User::factory()->editor()->create());
        Fixture::factory()->create(['external_source' => 'provider-a', 'external_id' => '555']);
        [$home, $away] = Team::factory()->count(2)->create();

        fillFixtureForm(Livewire::test(Fixtures::class), Season::factory()->create(), $home, $away)
            ->set('external_source', 'provider-a')
            ->set('external_id', '555')
            ->call('save')
            ->assertHasErrors(['external_id' => 'unique']);
    });

    it('updates the standings when a result is entered', function () {
        $this->actingAs(User::factory()->editor()->create());
        $fixture = Fixture::factory()->create();

        Livewire::test(Fixtures::class)
            ->call('edit', $fixture->id)
            ->set('fixture_status', 'finished')
            ->set('score_home', '3')
            ->set('score_away', '0')
            ->call('save')
            ->assertHasNoErrors();

        expect($fixture->season->standings()->where('team_id', $fixture->home_team_id)->value('points'))->toBe(3);
    });
});

it('filters fixtures by season', function () {
    $this->actingAs(User::factory()->editor()->create());
    $shown = Fixture::factory()->create();
    Fixture::factory()->create();

    $component = Livewire::test(Fixtures::class)->set('season', (string) $shown->season_id);

    expect($component->instance()->fixtures->modelKeys())->toBe([$shown->id]);
});

it('deletes a fixture', function () {
    $this->actingAs(User::factory()->editor()->create());
    $fixture = Fixture::factory()->create();

    Livewire::test(Fixtures::class)->call('delete', $fixture->id);

    expect(Fixture::query()->find($fixture->id))->toBeNull();
});
