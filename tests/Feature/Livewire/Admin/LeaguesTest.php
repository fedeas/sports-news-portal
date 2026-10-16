<?php

use App\Livewire\Admin\Leagues;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.leagues.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.leagues.index'))
            ->assertForbidden();
    });

    it('renders leagues for editors with names escaped', function () {
        League::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.leagues.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('saving leagues', function () {
    it('creates a league with a generated slug and tier', function () {
        $this->actingAs(User::factory()->editor()->create());
        $sport = Sport::factory()->create();

        Livewire::test(Leagues::class)
            ->call('create')
            ->set('name', 'Πρωτάθλημα Κύπρου')
            ->set('sport_id', (string) $sport->id)
            ->set('tier', '1')
            ->call('save')
            ->assertHasNoErrors();

        $league = League::query()->sole();

        expect($league->slug)->toBe('protathlima-kyprou')
            ->and($league->tier)->toBe(1);
    });

    it('rejects a tier outside 1 to 20', function (string $tier) {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Leagues::class)
            ->call('create')
            ->set('name', 'Πρωτάθλημα Κύπρου')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('tier', $tier)
            ->call('save')
            ->assertHasErrors(['tier']);
    })->with(['0', '21']);

    it('rejects an external ID already used by the same source', function () {
        $this->actingAs(User::factory()->editor()->create());
        League::factory()->create(['external_source' => 'provider-a', 'external_id' => '39']);

        Livewire::test(Leagues::class)
            ->call('create')
            ->set('name', 'Πρωτάθλημα Κύπρου')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('external_source', 'provider-a')
            ->set('external_id', '39')
            ->call('save')
            ->assertHasErrors(['external_id' => 'unique']);
    });
});

it('keeps a league whose seasons have fixtures', function () {
    $this->actingAs(User::factory()->editor()->create());
    $fixture = Fixture::factory()->create();

    Livewire::test(Leagues::class)->call('delete', $fixture->season->league_id);

    expect(League::query()->find($fixture->season->league_id))->not->toBeNull();
});

it('deletes a league together with its seasons', function () {
    $this->actingAs(User::factory()->editor()->create());
    $season = Season::factory()->create();

    Livewire::test(Leagues::class)->call('delete', $season->league_id);

    expect(League::query()->find($season->league_id))->toBeNull()
        ->and(Season::query()->find($season->id))->toBeNull();
});
