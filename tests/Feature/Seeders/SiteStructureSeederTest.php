<?php

use App\Models\Category;
use App\Models\League;
use App\Models\Sport;
use Database\Seeders\SiteStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates the sections, subsections and competitions of the site', function () {
    $this->seed(SiteStructureSeeder::class);

    $football = Category::query()->where('slug', 'podosfairo')->sole();
    $cyprus = Category::query()->where('slug', 'kypros')->sole();
    $cypriotBasketball = Category::query()->where('slug', 'basket-kypros')->sole();

    expect($cyprus->parent_id)->toBe($football->id)
        ->and($cyprus->name)->toBe('Κύπρος')
        // The same section name for another sport keeps its own slug.
        ->and($cypriotBasketball->name)->toBe('Κύπρος')
        ->and($cypriotBasketball->sport->slug)->toBe('basket')
        ->and(League::query()->where('slug', 'protathlima-a-katigorias')->sole()->tier)->toBe(1)
        ->and(Sport::query()->pluck('slug')->sort()->values()->all())->toBe(['alla-spor', 'basket', 'podosfairo', 'volei']);
});

it('runs again without duplicating anything and puts sections back in order', function () {
    $this->seed(SiteStructureSeeder::class);
    $cyprus = Category::query()->where('slug', 'kypros')->sole();
    $cyprus->update(['sort_order' => 99, 'name' => 'Κύπρος (μετονομασμένο)']);

    $this->seed(SiteStructureSeeder::class);

    expect(Category::query()->count())->toBe(22)
        ->and(League::query()->count())->toBe(16)
        ->and($cyprus->refresh()->sort_order)->toBe(1)
        // A name an editor changed is theirs to keep.
        ->and($cyprus->name)->toBe('Κύπρος (μετονομασμένο)');
});
