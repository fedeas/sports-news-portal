<?php

use App\Actions\RefreshLiveScores;
use App\Livewire\LiveScores;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cachedFixture(array $overrides = []): array
{
    return [
        'id' => 7,
        'url' => 'https://example.test/fixtures/7',
        'status' => 'live',
        'minute' => 63,
        'kickoff_at' => '2026-09-17T17:00:00+00:00',
        'score_home' => 2,
        'score_away' => 1,
        'league' => 'Πρωτάθλημα Κύπρου',
        'home' => ['name' => 'Ολυμπιάδα Λευκωσίας', 'short_name' => 'ΟΛΥ', 'url' => 'https://example.test/teams/olympiada'],
        'away' => ['name' => 'Νέα Πάφος', 'short_name' => 'ΠΑΦ', 'url' => 'https://example.test/teams/nea-pafos'],
        ...$overrides,
    ];
}

beforeEach(function () {
    Http::preventStrayRequests();
});

it('renders today\'s matches from the cache without touching the database or the provider', function () {
    Cache::forever(RefreshLiveScores::CACHE_KEY, ['refreshed_at' => now()->toIso8601String(), 'fixtures' => [cachedFixture()]]);
    DB::enableQueryLog();

    // The scoreboard uses the short codes, so a row of matches fits across a phone.
    Livewire::test(LiveScores::class)
        ->assertSee('ΟΛΥ')
        ->assertSee('ΠΑΦ')
        ->assertSee('63′')
        ->call('$refresh')
        ->assertSee('ΟΛΥ');

    expect(DB::getQueryLog())->toBe([]);
    Http::assertNothingSent();
});

it('renders nothing when there are no matches today', function () {
    Livewire::test(LiveScores::class)
        ->assertDontSee(__('Today'));
});

it('shows a single fixture from the initial data until it is cached, then follows the cache', function () {
    $component = Livewire::test(LiveScores::class, [
        'fixtureId' => 7,
        'initial' => cachedFixture(['status' => 'scheduled', 'score_home' => null, 'score_away' => null, 'minute' => null]),
    ])->assertSee(__('Scheduled'));

    Cache::forever(RefreshLiveScores::CACHE_KEY, ['refreshed_at' => now()->toIso8601String(), 'fixtures' => [cachedFixture(['score_home' => 3])]]);

    $component->call('$refresh')
        ->assertSee('3 – 1')
        ->assertSee(__('Live'));
});

it('escapes team names from the cache', function () {
    Cache::forever(RefreshLiveScores::CACHE_KEY, ['refreshed_at' => now()->toIso8601String(), 'fixtures' => [
        cachedFixture(['home' => ['name' => '<script>alert(1)</script>', 'short_name' => null, 'url' => 'https://example.test/teams/x']]),
    ]]);

    Livewire::test(LiveScores::class)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('does not let the browser change which fixture is shown', function () {
    expect(fn () => Livewire::test(LiveScores::class, ['fixtureId' => 7])->set('fixtureId', 8))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('falls back to the full team name when there is no short code', function () {
    Cache::forever(RefreshLiveScores::CACHE_KEY, ['refreshed_at' => now()->toIso8601String(), 'fixtures' => [cachedFixture([
        'home' => ['name' => 'Ολυμπιάδα Λευκωσίας', 'short_name' => null, 'url' => 'https://example.test/teams/olympiada'],
    ])]]);

    Livewire::test(LiveScores::class)->assertSee('Ολυμπιάδα Λευκωσίας');
});
