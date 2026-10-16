<?php

use App\Models\League;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('names the country in the current locale', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect((new Team(['country' => 'CY']))->countryName())->toBe($expected);
})->with([
    'greek' => ['el', 'Κύπρος'],
    'english' => ['en', 'Cyprus'],
])->skip(! extension_loaded('intl'), 'The intl extension is not installed.');

it('falls back to the code for an unknown country and to null without one', function (string $model) {
    expect((new $model(['country' => 'ZZ']))->countryName())->toBe('ZZ')
        ->and((new $model(['country' => null]))->countryName())->toBeNull();
})->with([Team::class, League::class]);

it('shows the country name on team and league pages', function () {
    app()->setLocale('el');
    Team::factory()->create(['slug' => 'omonoia', 'country' => 'CY']);
    League::factory()->create(['slug' => 'super-league', 'country' => 'GR']);

    $this->get('/teams/omonoia')->assertSee('Κύπρος');
    $this->get('/leagues/super-league')->assertSee('Ελλάδα');
})->skip(! extension_loaded('intl'), 'The intl extension is not installed.');
