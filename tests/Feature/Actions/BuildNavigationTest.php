<?php

use App\Actions\BuildNavigation;
use App\Enums\SectionIcon;
use App\Models\Category;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the active sections with their subsections, in order', function () {
    $football = Category::factory()->create(['name' => 'Ποδόσφαιρο', 'slug' => 'podosfairo', 'icon' => SectionIcon::Football, 'sort_order' => 1]);
    Category::factory()->childOf($football)->create(['name' => 'Κύπρος', 'slug' => 'kypros', 'sort_order' => 1]);
    Category::factory()->childOf($football)->inactive()->create(['name' => 'Κρυφή']);
    Category::factory()->create(['name' => 'Μπάσκετ', 'sort_order' => 2]);
    Category::factory()->inactive()->create(['name' => 'Κρυφή ενότητα']);

    $navigation = app(BuildNavigation::class)->handle();

    expect(array_column($navigation, 'name'))->toBe(['Ποδόσφαιρο', 'Μπάσκετ'])
        ->and($navigation[0]['url'])->toBe(url('/podosfairo'))
        ->and($navigation[0]['icon'])->toBe('football')
        ->and($navigation[0]['children'])->toBe([['name' => 'Κύπρος', 'url' => url('/podosfairo/kypros'), 'icon' => null]]);
});

it('lists the competitions of the section\'s sport with the clubs of the current season', function () {
    $football = Sport::factory()->create(['slug' => 'podosfairo']);
    $basketball = Sport::factory()->create(['slug' => 'basket']);
    Category::factory()->for($football)->create(['name' => 'Ποδόσφαιρο']);
    $league = League::factory()->for($football)->create(['name' => 'Α΄ Κατηγορία', 'slug' => 'a-katigoria', 'tier' => 1]);
    League::factory()->for($basketball)->create(['name' => 'Μπάσκετ Α΄']);
    League::factory()->for($football)->inactive()->create(['name' => 'Κρυφό πρωτάθλημα']);

    $season = Season::factory()->current()->for($league)->create();
    $playing = Team::factory()->for($football)->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);
    $hidden = Team::factory()->for($football)->inactive()->create(['name' => 'Κρυφή ομάδα']);
    $season->teams()->attach([$playing->id, $hidden->id]);

    $navigation = app(BuildNavigation::class)->handle();

    expect($navigation[0]['leagues'])->toBe([[
        'name' => 'Α΄ Κατηγορία',
        'url' => url('/leagues/a-katigoria'),
        'teams' => [['name' => 'ΑΠΟΕΛ', 'url' => url('/teams/apoel')]],
    ]]);
});

it('opens the menu without JavaScript, as a details element', function () {
    Category::factory()->create(['name' => 'Ποδόσφαιρο', 'slug' => 'podosfairo']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<details class="sections-menu" data-sections-menu>', false)
        ->assertSee('<summary', false);
});

it('shows the whole tree in the menu of every page', function () {
    $football = Sport::factory()->create();
    $section = Category::factory()->for($football)->create(['name' => 'Ποδόσφαιρο', 'slug' => 'podosfairo']);
    Category::factory()->childOf($section)->create(['name' => 'Κύπρος', 'slug' => 'kypros']);
    $league = League::factory()->for($football)->create(['name' => 'Α΄ Κατηγορία', 'slug' => 'a-katigoria']);
    $team = Team::factory()->for($football)->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);
    Season::factory()->current()->for($league)->create()->teams()->attach($team);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('All sections'))
        ->assertSee(url('/podosfairo/kypros'), false)
        ->assertSee(url('/leagues/a-katigoria'), false)
        ->assertSee(url('/teams/apoel'), false);
});
