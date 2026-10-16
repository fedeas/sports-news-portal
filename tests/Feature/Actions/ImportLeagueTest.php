<?php

use App\Actions\ImportLeague;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Services\SportsData\Data\LeagueData;
use App\Services\SportsData\Data\SeasonData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function leagueData(string $seasonKey = '2025', string $endsOn = '2026-05-16', bool $isCurrent = true): LeagueData
{
    return new LeagueData('318', 'Demo First Division', 'CY', new SeasonData(
        $seasonKey,
        $seasonKey.'/'.((int) $seasonKey + 1),
        CarbonImmutable::parse(((int) $seasonKey).'-08-22'),
        CarbonImmutable::parse($endsOn),
        $isCurrent,
    ));
}

it('creates the league and its season on the first import', function () {
    $sport = Sport::factory()->create();

    [$league, $season] = app(ImportLeague::class)->handle('api-football', leagueData(), $sport);

    expect($league->only(['external_source', 'external_id', 'name', 'slug', 'country', 'sport_id']))->toBe([
        'external_source' => 'api-football',
        'external_id' => '318',
        'name' => 'Demo First Division',
        'slug' => 'demo-first-division',
        'country' => 'CY',
        'sport_id' => $sport->id,
    ])
        ->and($season->name)->toBe('2025/2026')
        ->and($season->external_id)->toBe('2025')
        ->and($season->is_current)->toBeTrue();
});

it('keeps editorial changes to the league and updates the season on later imports', function () {
    $sport = Sport::factory()->create();
    [$league] = app(ImportLeague::class)->handle('api-football', leagueData(), $sport);
    $league->update(['name' => 'Α΄ Κατηγορία', 'slug' => 'a-katigoria']);

    [, $season] = app(ImportLeague::class)->handle('api-football', leagueData(endsOn: '2026-05-30'), $sport);

    expect(League::query()->count())->toBe(1)
        ->and($league->refresh()->name)->toBe('Α΄ Κατηγορία')
        ->and($league->slug)->toBe('a-katigoria')
        ->and(Season::query()->count())->toBe(1)
        ->and($season->refresh()->ends_on->toDateString())->toBe('2026-05-30');
});

it('leaves only the imported current season marked as current', function () {
    $sport = Sport::factory()->create();
    app(ImportLeague::class)->handle('api-football', leagueData('2024', '2025-05-17'), $sport);

    [, $current] = app(ImportLeague::class)->handle('api-football', leagueData('2025'), $sport);

    expect(Season::query()->where('is_current', true)->pluck('id')->all())->toBe([$current->id]);
});
