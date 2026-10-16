<?php

namespace Database\Seeders;

use App\Actions\MakeSlug;
use App\Enums\FixtureStatus;
use App\Models\Article;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Seeder;

/**
 * Local demo: the Cypriot first division clubs, with invented fixtures, results and standings.
 *
 * The club names are real so the pages look like the finished site; everything else (scores,
 * rounds, tables) is made up demo data and must never be published as if it were news.
 * Club badges are not used: the pages draw a placeholder from the club's initials.
 *
 * Safe to run repeatedly: `php artisan db:seed --class=DemoTeamsSeeder`.
 */
class DemoTeamsSeeder extends Seeder
{
    /**
     * Cypriot first division clubs. Founding years are from public club histories.
     *
     * @var array<int, array{name: string, short: string, founded: int}>
     */
    public const TEAMS = [
        ['name' => 'ΑΠΟΕΛ', 'short' => 'ΑΠΟΕΛ', 'founded' => 1926],
        ['name' => 'Ομόνοια Λευκωσίας', 'short' => 'ΟΜΟ', 'founded' => 1948],
        ['name' => 'Απόλλων Λεμεσού', 'short' => 'ΑΠΟ', 'founded' => 1954],
        ['name' => 'ΑΕΛ Λεμεσού', 'short' => 'ΑΕΛ', 'founded' => 1930],
        ['name' => 'Ανόρθωση Αμμοχώστου', 'short' => 'ΑΝΟ', 'founded' => 1911],
        ['name' => 'Νέα Σαλαμίνα Αμμοχώστου', 'short' => 'ΝΣΑ', 'founded' => 1948],
        ['name' => 'ΑΕΚ Λάρνακας', 'short' => 'ΑΕΚ', 'founded' => 1994],
        ['name' => 'Άρης Λεμεσού', 'short' => 'ΑΡΗ', 'founded' => 1930],
        ['name' => 'Πάφος FC', 'short' => 'ΠΑΦ', 'founded' => 2014],
        ['name' => 'Ένωσις Νέων Παραλιμνίου', 'short' => 'ΕΝΠ', 'founded' => 1948],
        ['name' => 'Ολυμπιακός Λευκωσίας', 'short' => 'ΟΛΥ', 'founded' => 1931],
        ['name' => 'Δόξα Κατωκοπιάς', 'short' => 'ΔΟΞ', 'founded' => 1954],
    ];

    /**
     * Rounds of the current season that already have a result; the rest are upcoming.
     */
    private const PLAYED_ROUNDS = 3;

    private const UPCOMING_ROUNDS = 2;

    public function run(MakeSlug $makeSlug): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Demo content must never be seeded in production.');

            return;
        }

        $football = Sport::query()->firstOrCreate(['slug' => 'podosfairo'], ['name' => 'Ποδόσφαιρο', 'sort_order' => 1]);

        foreach (self::TEAMS as $team) {
            Team::query()->firstOrCreate(['slug' => $makeSlug->handle($team['name'])], [
                'sport_id' => $football->id,
                'name' => $team['name'],
                'short_name' => $team['short'],
                'country' => 'CY',
                'founded_year' => $team['founded'],
                'is_active' => true,
            ]);
        }

        $league = League::query()->firstOrCreate(['slug' => 'protathlima-a-katigorias'], [
            'sport_id' => $football->id,
            'name' => 'Πρωτάθλημα Α΄ Κατηγορίας',
            'country' => 'CY',
            'tier' => 1,
            'is_active' => true,
        ]);

        Season::query()->firstOrCreate(
            ['league_id' => $league->id, 'name' => '2024/2025'],
            ['starts_on' => '2024-08-24', 'ends_on' => '2025-05-18', 'is_current' => false],
        );

        $currentSeason = Season::query()->firstOrCreate(
            ['league_id' => $league->id, 'name' => '2025/2026'],
            ['starts_on' => '2025-08-23', 'ends_on' => '2026-05-17', 'is_current' => true],
        );

        $this->seedFixtures($makeSlug, $currentSeason);
        $this->linkDemoArticles($makeSlug, $league);

        $this->command?->info('Demo teams and league ready.');
    }

    /**
     * Put the demo teams in the current season with three played and two upcoming rounds.
     * Standings are rebuilt automatically as the finished fixtures are saved.
     */
    private function seedFixtures(MakeSlug $makeSlug, Season $season): void
    {
        $teamIds = Team::query()
            ->whereIn('slug', collect(self::TEAMS)->map(fn (array $team) => $makeSlug->handle($team['name'])))
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $season->teams()->syncWithoutDetaching($teamIds);

        if (count($teamIds) < 4 || $season->fixtures()->exists()) {
            return;
        }

        $rounds = array_slice($this->rounds(count($teamIds)), 0, self::PLAYED_ROUNDS + self::UPCOMING_ROUNDS);

        foreach ($rounds as $roundIndex => $matches) {
            $isPlayed = $roundIndex < self::PLAYED_ROUNDS;

            foreach ($matches as $matchIndex => [$home, $away]) {
                Fixture::query()->create([
                    'season_id' => $season->id,
                    'home_team_id' => $teamIds[$home],
                    'away_team_id' => $teamIds[$away],
                    'kickoff_at' => ($isPlayed ? now()->subWeeks(self::PLAYED_ROUNDS - $roundIndex) : now()->addWeeks($roundIndex - self::PLAYED_ROUNDS + 1))
                        ->setTime(15 + ($matchIndex % 4) * 2, ($matchIndex % 2) * 30),
                    'status' => $isPlayed ? FixtureStatus::Finished : FixtureStatus::Scheduled,
                    'score_home' => $isPlayed ? ($roundIndex + $matchIndex * 2) % 4 : null,
                    'score_away' => $isPlayed ? ($matchIndex + 1) % 3 : null,
                    'round' => (string) ($roundIndex + 1),
                ]);
            }
        }
    }

    /**
     * A round-robin draw (the circle method): every round pairs all teams once.
     *
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    private function rounds(int $teamCount): array
    {
        $positions = range(0, $teamCount - 1);
        $rounds = [];

        foreach (range(1, $teamCount - 1) as $round) {
            $matches = [];

            for ($match = 0; $match < intdiv($teamCount, 2); $match++) {
                $home = $positions[$match];
                $away = $positions[$teamCount - 1 - $match];

                // Alternate home advantage, as a real fixture list does.
                $matches[] = $round % 2 === 0 ? [$away, $home] : [$home, $away];
            }

            $rounds[] = $matches;

            // Keep the first team fixed and rotate the rest.
            $positions = [$positions[0], ...array_slice($positions, -1), ...array_slice($positions, 1, $teamCount - 2)];
        }

        return $rounds;
    }

    /**
     * Link existing articles to the demo teams named in their titles, and to the demo league.
     */
    private function linkDemoArticles(MakeSlug $makeSlug, League $league): void
    {
        $teams = Team::query()
            ->whereIn('slug', collect(self::TEAMS)->map(fn (array $team) => $makeSlug->handle($team['name'])))
            ->get(['id', 'name']);

        Article::query()->get(['id', 'title'])->each(function (Article $article) use ($teams, $league) {
            $teamIds = $teams->filter(fn (Team $team) => str_contains($article->title, $team->name))->modelKeys();

            if ($teamIds !== []) {
                $article->teams()->syncWithoutDetaching($teamIds);
                $article->leagues()->syncWithoutDetaching([$league->id]);
            }
        });
    }
}
