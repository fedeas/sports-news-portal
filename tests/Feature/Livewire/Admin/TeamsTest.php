<?php

use App\Enums\MediaCollection;
use App\Livewire\Admin\Teams;
use App\Models\Fixture;
use App\Models\Media;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.teams.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.teams.index'))
            ->assertForbidden();
    });

    it('renders teams for editors with names escaped', function () {
        Team::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.teams.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('saving teams', function () {
    it('creates a team with a generated slug and an uppercased country code', function () {
        $this->actingAs(User::factory()->editor()->create());
        $sport = Sport::factory()->create();

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) $sport->id)
            ->set('country', 'cy')
            ->set('founded_year', '1927')
            ->call('save')
            ->assertHasNoErrors();

        $team = Team::query()->sole();

        expect($team->slug)->toBe('olympiada-lefkosias')
            ->and($team->country)->toBe('CY')
            ->and($team->founded_year)->toBe(1927)
            ->and($team->sport_id)->toBe($sport->id);
    });

    it('rejects invalid values', function (string $field, string $value) {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set($field, $value)
            ->call('save')
            ->assertHasErrors([$field]);

        expect(Team::query()->count())->toBe(0);
    })->with([
        'country name instead of code' => ['country', 'Cyprus'],
        'founded in the future' => ['founded_year', (string) (now()->year + 1)],
        'sport that does not exist' => ['sport_id', '999'],
    ]);

    it('requires the external source and ID together', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('external_id', '123')
            ->call('save')
            ->assertHasErrors(['external_source' => 'required_with']);
    });

    it('rejects an external ID already used by the same source', function () {
        $this->actingAs(User::factory()->editor()->create());
        Team::factory()->create(['external_source' => 'provider-a', 'external_id' => '10']);

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('external_source', 'provider-a')
            ->set('external_id', '10')
            ->call('save')
            ->assertHasErrors(['external_id' => 'unique']);
    });

    it('accepts the same external ID from a different source', function () {
        $this->actingAs(User::factory()->editor()->create());
        Team::factory()->create(['external_source' => 'provider-a', 'external_id' => '10']);

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('external_source', 'provider-b')
            ->set('external_id', '10')
            ->call('save')
            ->assertHasNoErrors();
    });

    it('updates a team and keeps its slug', function () {
        $this->actingAs(User::factory()->editor()->create());
        $team = Team::factory()->create(['name' => 'Ολυμπιάδα', 'slug' => 'olympiada']);

        Livewire::test(Teams::class)
            ->call('edit', $team->id)
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->call('save')
            ->assertHasNoErrors();

        expect($team->refresh()->name)->toBe('Ολυμπιάδα Λευκωσίας')
            ->and($team->slug)->toBe('olympiada');
    });

    it('does not allow the edited team id to be changed from the browser', function () {
        $this->actingAs(User::factory()->editor()->create());

        expect(fn () => Livewire::test(Teams::class)->set('editingId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('logos', function () {
    it('stores an uploaded logo owned by the team', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('logo', UploadedFile::fake()->image('crest.png', 300, 300))
            ->call('save')
            ->assertHasNoErrors();

        $logo = Team::query()->with('logo')->sole()->logo;

        expect($logo->collection)->toBe(MediaCollection::Logo)
            ->and($logo->alt_text)->toBe('Ολυμπιάδα Λευκωσίας')
            ->and($logo->mediable_type)->toBe('team');
    });

    it('deletes the previous logo when a new one is uploaded', function () {
        $this->actingAs(User::factory()->editor()->create());
        $oldLogo = Media::factory()->create();
        $team = Team::factory()->create(['logo_media_id' => $oldLogo->id]);

        Livewire::test(Teams::class)
            ->call('edit', $team->id)
            ->set('logo', UploadedFile::fake()->image('crest.png', 300, 300))
            ->call('save')
            ->assertHasNoErrors();

        expect(Media::query()->find($oldLogo->id))->toBeNull()
            ->and($team->refresh()->logo_media_id)->not->toBeNull();
    });

    it('rejects an SVG logo', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Teams::class)
            ->call('create')
            ->set('name', 'Ολυμπιάδα Λευκωσίας')
            ->set('sport_id', (string) Sport::factory()->create()->id)
            ->set('logo', UploadedFile::fake()->createWithContent('crest.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
            ->call('save')
            ->assertHasErrors(['logo']);

        expect(Team::query()->count())->toBe(0);
    });
});

it('keeps a team that still has fixtures', function () {
    $this->actingAs(User::factory()->editor()->create());
    $fixture = Fixture::factory()->create();

    Livewire::test(Teams::class)->call('delete', $fixture->away_team_id);

    expect(Team::query()->find($fixture->away_team_id))->not->toBeNull();
});

it('deletes a team together with its logo', function () {
    $this->actingAs(User::factory()->editor()->create());
    $logo = Media::factory()->create();
    $team = Team::factory()->create(['logo_media_id' => $logo->id]);

    Livewire::test(Teams::class)->call('delete', $team->id);

    expect(Team::query()->find($team->id))->toBeNull()
        ->and(Media::query()->find($logo->id))->toBeNull();
});
