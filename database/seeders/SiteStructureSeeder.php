<?php

namespace Database\Seeders;

use App\Enums\SectionIcon;
use App\Models\Category;
use App\Models\League;
use App\Models\Sport;
use Illuminate\Database\Seeder;

/**
 * The sections and competitions the site covers.
 *
 * Structure only: no articles, no results. Safe to run again after adding a section or a
 * competition here, because everything is matched on its slug.
 *
 * Run it with `php artisan db:seed --class=SiteStructureSeeder`.
 */
class SiteStructureSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, slug: string, icon?: string}>
     */
    private const SPORTS = [
        ['name' => 'Ποδόσφαιρο', 'slug' => 'podosfairo'],
        ['name' => 'Μπάσκετ', 'slug' => 'basket'],
        ['name' => 'Βόλεϊ', 'slug' => 'volei'],
        ['name' => 'Άλλα σπορ', 'slug' => 'alla-spor'],
    ];

    /**
     * Sections, their subsections, and the sport whose competitions belong to them.
     *
     * Category slugs are unique across the whole site, so a section that exists for several
     * sports carries the sport in its slug (`basket-kypros`), while its name stays "Κύπρος".
     *
     * @var array<int, array{name: string, slug: string, icon?: SectionIcon, sport: ?string, children: array<int, array{name: string, slug: string, icon?: SectionIcon}>}>
     */
    private const SECTIONS = [
        [
            'name' => 'Ποδόσφαιρο',
            'slug' => 'podosfairo',
            'icon' => SectionIcon::Football,
            'sport' => 'podosfairo',
            'children' => [
                ['name' => 'Κύπρος', 'slug' => 'kypros'],
                ['name' => 'Ελλάδα', 'slug' => 'ellada'],
                ['name' => 'Αγγλία', 'slug' => 'agglia'],
                ['name' => 'Ισπανία', 'slug' => 'ispania'],
                ['name' => 'Ιταλία', 'slug' => 'italia'],
                ['name' => 'Γερμανία', 'slug' => 'germania'],
                ['name' => 'Διεθνή', 'slug' => 'diethni'],
            ],
        ],
        [
            'name' => 'Μπάσκετ',
            'slug' => 'basket',
            'icon' => SectionIcon::Basketball,
            'sport' => 'basket',
            'children' => [
                ['name' => 'Κύπρος', 'slug' => 'basket-kypros'],
                ['name' => 'Ελλάδα', 'slug' => 'basket-ellada'],
                ['name' => 'Διεθνή', 'slug' => 'basket-diethni'],
            ],
        ],
        [
            'name' => 'Βόλεϊ',
            'slug' => 'volei',
            'icon' => SectionIcon::Volleyball,
            'sport' => 'volei',
            'children' => [
                ['name' => 'Κύπρος', 'slug' => 'volei-kypros'],
                ['name' => 'Διεθνή', 'slug' => 'volei-diethni'],
            ],
        ],
        [
            'name' => 'Άλλα σπορ',
            'slug' => 'alla-spor',
            'icon' => SectionIcon::OtherSports,
            'sport' => 'alla-spor',
            'children' => [
                ['name' => 'Τένις', 'slug' => 'tenis', 'icon' => SectionIcon::Tennis],
                ['name' => 'Στίβος', 'slug' => 'stivos', 'icon' => SectionIcon::Athletics],
                ['name' => 'Μαχητικά αθλήματα', 'slug' => 'machitika-athlimata', 'icon' => SectionIcon::CombatSports],
            ],
        ],
        ['name' => 'Μεταγραφές', 'slug' => 'metagrafes', 'icon' => SectionIcon::Transfers, 'sport' => null, 'children' => []],
        ['name' => 'Εθνικές ομάδες', 'slug' => 'ethnikes-omades', 'icon' => SectionIcon::NationalTeams, 'sport' => null, 'children' => []],
        ['name' => 'Απόψεις', 'slug' => 'apopseis', 'icon' => SectionIcon::Opinion, 'sport' => null, 'children' => []],
    ];

    /**
     * Competitions, in the order they should appear under their sport.
     *
     * @var array<int, array{name: string, slug: string, sport: string, country: ?string, tier: ?int}>
     */
    private const LEAGUES = [
        ['name' => 'Πρωτάθλημα Α΄ Κατηγορίας', 'slug' => 'protathlima-a-katigorias', 'sport' => 'podosfairo', 'country' => 'CY', 'tier' => 1],
        ['name' => 'Πρωτάθλημα Β΄ Κατηγορίας', 'slug' => 'protathlima-b-katigorias', 'sport' => 'podosfairo', 'country' => 'CY', 'tier' => 2],
        ['name' => 'Κύπελλο Κύπρου', 'slug' => 'kypello-kyprou', 'sport' => 'podosfairo', 'country' => 'CY', 'tier' => null],
        ['name' => 'Super League', 'slug' => 'super-league', 'sport' => 'podosfairo', 'country' => 'GR', 'tier' => 1],
        ['name' => 'Premier League', 'slug' => 'premier-league', 'sport' => 'podosfairo', 'country' => 'GB', 'tier' => 1],
        ['name' => 'La Liga', 'slug' => 'la-liga', 'sport' => 'podosfairo', 'country' => 'ES', 'tier' => 1],
        ['name' => 'Serie A', 'slug' => 'serie-a', 'sport' => 'podosfairo', 'country' => 'IT', 'tier' => 1],
        ['name' => 'Bundesliga', 'slug' => 'bundesliga', 'sport' => 'podosfairo', 'country' => 'DE', 'tier' => 1],
        ['name' => 'Champions League', 'slug' => 'champions-league', 'sport' => 'podosfairo', 'country' => null, 'tier' => null],
        ['name' => 'Europa League', 'slug' => 'europa-league', 'sport' => 'podosfairo', 'country' => null, 'tier' => null],
        ['name' => 'Conference League', 'slug' => 'conference-league', 'sport' => 'podosfairo', 'country' => null, 'tier' => null],

        ['name' => 'Α΄ Κατηγορία Ανδρών', 'slug' => 'basket-a-katigoria-andron', 'sport' => 'basket', 'country' => 'CY', 'tier' => 1],
        ['name' => 'Basket League', 'slug' => 'basket-league', 'sport' => 'basket', 'country' => 'GR', 'tier' => 1],
        ['name' => 'EuroLeague', 'slug' => 'euroleague', 'sport' => 'basket', 'country' => null, 'tier' => null],
        ['name' => 'NBA', 'slug' => 'nba', 'sport' => 'basket', 'country' => 'US', 'tier' => 1],

        ['name' => 'Α΄ Κατηγορία Ανδρών', 'slug' => 'volei-a-katigoria-andron', 'sport' => 'volei', 'country' => 'CY', 'tier' => 1],
    ];

    public function run(): void
    {
        $sports = $this->sports();

        foreach (self::SECTIONS as $order => $section) {
            $parent = $this->category($section, $sports[$section['sport']] ?? null, $order + 1);

            foreach ($section['children'] as $childOrder => $child) {
                $this->category(
                    [...$child, 'sport' => $section['sport']],
                    $sports[$section['sport']] ?? null,
                    $childOrder + 1,
                    $parent,
                );
            }
        }

        foreach (self::LEAGUES as $league) {
            League::query()->firstOrCreate(['slug' => $league['slug']], [
                'sport_id' => $sports[$league['sport']]->id,
                'name' => $league['name'],
                'country' => $league['country'],
                'tier' => $league['tier'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Sections and competitions ready.');
    }

    /**
     * @return array<string, Sport>
     */
    private function sports(): array
    {
        $sports = [];

        foreach (self::SPORTS as $order => $sport) {
            $sports[$sport['slug']] = Sport::query()->firstOrCreate(
                ['slug' => $sport['slug']],
                ['name' => $sport['name'], 'sort_order' => $order + 1, 'is_active' => true],
            );
        }

        return $sports;
    }

    /**
     * @param  array{name: string, slug: string, icon?: SectionIcon}  $section
     */
    private function category(array $section, ?Sport $sport, int $sortOrder, ?Category $parent = null): Category
    {
        $category = Category::query()->firstOrCreate(['slug' => $section['slug']], [
            'sport_id' => $sport?->id,
            'parent_id' => $parent?->id,
            'name' => $section['name'],
            'description' => $parent === null
                ? "Ειδήσεις, αποτελέσματα και αναλύσεις: {$section['name']}."
                : "{$section['name']}: ειδήσεις, αποτελέσματα και αναλύσεις από {$parent->name}.",
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);

        // Where a section sits and in what order is structure, so keep it in step with this file.
        // Everything an editor writes — the name, the description, the meta tags — is left alone.
        $category->fill(['sport_id' => $sport?->id, 'parent_id' => $parent?->id, 'sort_order' => $sortOrder]);

        // A pictogram only where the section has none yet, so an editor's choice stands.
        $category->icon ??= $section['icon'] ?? null;

        $category->save();

        return $category;
    }
}
