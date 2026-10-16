<?php

namespace Database\Seeders;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Actions\UploadMedia;
use App\Enums\ArticleStatus;
use App\Enums\MediaCollection;
use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Media;
use App\Models\Sport;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\LineFactory;
use Intervention\Image\Geometry\Factories\PolygonFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Stevebauman\Purify\Facades\Purify;

/**
 * Local demo content so the public pages can be checked visually.
 *
 * Every person, result and sentence is invented and the photos are generated locally; only the
 * club names are real, so the pages look like the finished site. Never publish this as news.
 * Run it explicitly with `php artisan db:seed --class=DemoContentSeeder`.
 */
class DemoContentSeeder extends Seeder
{
    public const ARTICLE_COUNT = 48;

    /**
     * The clubs written about, in the order they appear in the demo headlines.
     *
     * @return array<int, string>
     */
    private function teams(): array
    {
        return array_column(DemoTeamsSeeder::TEAMS, 'name');
    }

    private const PARAGRAPHS = [
        'Η αναμέτρηση ξεκίνησε με γρήγορο ρυθμό και από τα πρώτα λεπτά φάνηκε ότι καμία ομάδα δεν ήθελε να αφήσει την πρωτοβουλία των κινήσεων στον αντίπαλο.',
        'Ο προπονητής επέλεξε να ξεκινήσει με τρεις αμυντικούς, μια απόφαση που έδωσε περισσότερη σταθερότητα στα άκρα και ελευθερία στους μέσους.',
        'Στο δεύτερο ημίχρονο οι αλλαγές έφεραν φρέσκα πόδια στη μεσαία γραμμή και η πίεση στην αντίπαλη περιοχή αυξήθηκε αισθητά.',
        'Η εξέδρα στήριξε την ομάδα σε όλη τη διάρκεια του αγώνα, δημιουργώντας ατμόσφαιρα που θύμισε τις μεγάλες βραδιές του παρελθόντος.',
        'Μετά τη λήξη, οι παίκτες μίλησαν για μια σημαντική νίκη που δίνει ώθηση πριν από το απαιτητικό πρόγραμμα των επόμενων εβδομάδων.',
        'Το τεχνικό επιτελείο θα αξιολογήσει την κατάσταση των τραυματιών στις προπονήσεις της εβδομάδας, πριν ανακοινώσει την αποστολή.',
        'Οι αριθμοί του αγώνα δείχνουν σαφή υπεροχή στην κατοχή της μπάλας, όμως η αποτελεσματικότητα στην τελική προσπάθεια παραμένει ζητούμενο.',
        'Η διοίκηση εξέφρασε την ικανοποίησή της για την πορεία της ομάδας και υπογράμμισε ότι ο στόχος παραμένει η διάκριση στο τέλος της σεζόν.',
        'Οι νεαροί παίκτες που ανέβηκαν από την ακαδημία πήραν χρόνο συμμετοχής και έδειξαν ότι μπορούν να ανταποκριθούν στις απαιτήσεις της κατηγορίας.',
        'Το επόμενο παιχνίδι είναι εκτός έδρας και η ομάδα θα ταξιδέψει χωρίς δύο βασικούς, που εκτίουν ποινή μετά τις κάρτες της περασμένης αγωνιστικής.',
        'Η άμυνα κράτησε το μηδέν για τρίτο συνεχόμενο ματς, κάτι που δεν είχε συμβεί από την αρχή της περιόδου.',
        'Στη συνέντευξη Τύπου έγινε λόγος για τη διαιτησία, με τις δύο πλευρές να διατυπώνουν εντελώς διαφορετική ανάγνωση των φάσεων.',
        'Τα εισιτήρια για την επόμενη εντός έδρας αναμέτρηση εξαντλήθηκαν μέσα σε λίγες ώρες, δείχνοντας το ενδιαφέρον του κόσμου.',
        'Η προετοιμασία για τη δεύτερη φάση ξεκινά αμέσως μετά τη διακοπή, με διπλές προπονήσεις και δύο φιλικά παιχνίδια.',
    ];

    /**
     * Background colours and the shapes drawn on the generated photo of each section.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const ARTWORK = [
        'podosfairo' => ['#052e16', '#16a34a', 'pitch'],
        'kypros' => ['#0c4a6e', '#0ea5e9', 'pitch'],
        'ellada' => ['#1e1b4b', '#6366f1', 'pitch'],
        'diethni' => ['#134e4a', '#14b8a6', 'pitch'],
        'basket' => ['#7c2d12', '#f97316', 'court'],
        'metagrafes' => ['#3b0764', '#a855f7', 'arrows'],
        'apopseis' => ['#450a0a', '#ef4444', 'bars'],
    ];

    private const PHOTO_WIDTH = 1600;

    private const PHOTO_HEIGHT = 900;

    public function run(ImageManager $images, UploadMedia $uploadMedia, GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Demo content must never be seeded in production.');

            return;
        }

        if (Article::query()->withTrashed()->exists()) {
            $this->command?->warn('Articles already exist; skipping demo content.');

            return;
        }

        $uploader = User::query()->orderBy('id')->first();

        if ($uploader === null) {
            $this->command?->error('Create a user first (php artisan db:seed).');

            return;
        }

        $sections = $this->sections();
        $authors = $this->authors();
        $teams = $this->teams();
        $tags = collect($teams)->map(fn (string $team) => Tag::query()->firstOrCreate(
            ['slug' => $makeSlug->handle($team)],
            ['name' => $team],
        ));

        foreach (range(0, self::ARTICLE_COUNT - 1) as $index) {
            $category = $sections[$index % count($sections)];
            $home = $teams[$index % count($teams)];
            $away = $teams[($index + 5) % count($teams)];
            $title = $this->title($category->slug, $home, $away, $index);

            $article = new Article([
                'category_id' => $category->id,
                'author_id' => $authors[$index % count($authors)]->id,
                'title' => $title,
                'slug' => $generateUniqueSlug->handle($title, Article::class, maxLength: 220, alsoUniqueAgainst: [Category::class]),
                // The body uses the five paragraphs starting at $index, so start the excerpt after them.
                'excerpt' => self::PARAGRAPHS[($index + 5) % count(self::PARAGRAPHS)],
                'body' => Purify::config('article')->clean($this->body($index)),
                'is_breaking' => in_array($index, [0, 3], true),
                'is_featured' => $index === 1,
            ]);

            $article->forceFill([
                'status' => match ($index) {
                    self::ARTICLE_COUNT - 2 => ArticleStatus::Draft,
                    self::ARTICLE_COUNT - 1 => ArticleStatus::Scheduled,
                    default => ArticleStatus::Published,
                },
                'published_at' => $index === self::ARTICLE_COUNT - 1 ? now()->addDay() : now()->subMinutes(20 + $index * 197),
                // One article without a photo, so the text-only card and page keep being checked.
                'featured_image_id' => $index === 6 ? null : $this->photo($images, $uploadMedia, $uploader, $category, $index)->id,
                'created_by' => $uploader->id,
            ])->save();

            $article->tags()->attach([$tags[$index % $tags->count()]->id, $tags[($index + 3) % $tags->count()]->id]);

            if ($index % 5 === 0) {
                $article->coAuthors()->attach($authors[($index + 1) % count($authors)]->id, ['sort_order' => 0]);
            }
        }

        $this->command?->info('Demo content created: '.self::ARTICLE_COUNT.' articles.');
    }

    /**
     * @return array<int, Category>
     */
    private function sections(): array
    {
        $football = Sport::query()->firstOrCreate(['slug' => 'podosfairo'], ['name' => 'Ποδόσφαιρο', 'sort_order' => 1]);
        $basketball = Sport::query()->firstOrCreate(['slug' => 'basket'], ['name' => 'Μπάσκετ', 'sort_order' => 2]);

        $footballSection = $this->category('Ποδόσφαιρο', 'podosfairo', 1, $football);
        $basketballSection = $this->category('Μπάσκετ', 'basket', 2, $basketball);
        $transfers = $this->category('Μεταγραφές', 'metagrafes', 3);
        $opinion = $this->category('Απόψεις', 'apopseis', 4);

        $cyprus = $this->category('Κύπρος', 'kypros', 1, $football, $footballSection);
        $greece = $this->category('Ελλάδα', 'ellada', 2, $football, $footballSection);
        $international = $this->category('Διεθνή', 'diethni', 3, $football, $footballSection);

        return [$cyprus, $greece, $basketballSection, $transfers, $cyprus, $international, $opinion, $footballSection];
    }

    private function category(string $name, string $slug, int $sortOrder, ?Sport $sport = null, ?Category $parent = null): Category
    {
        return Category::query()->firstOrCreate(['slug' => $slug], [
            'name' => $name,
            'sport_id' => $sport?->id,
            'parent_id' => $parent?->id,
            'description' => "Ειδήσεις, αποτελέσματα και αναλύσεις: {$name}.",
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, Author>
     */
    private function authors(): array
    {
        return collect([
            ['Ελένη Κωνσταντίνου', 'eleni-konstantinou'],
            ['Ανδρέας Γεωργίου', 'andreas-georgiou'],
            ['Σοφία Νικολάου', 'sofia-nikolaou'],
            ['Μάριος Ιωάννου', 'marios-ioannou'],
        ])->map(fn (array $author) => Author::query()->firstOrCreate(['slug' => $author[1]], [
            'name' => $author[0],
            'bio' => 'Αθλητικός συντάκτης με ειδίκευση στο ποδόσφαιρο και το μπάσκετ.',
            'is_active' => true,
        ]))->all();
    }

    /**
     * A drawn placeholder photo for one article, stored through the real upload pipeline.
     *
     * Nothing is downloaded: every photo is a gradient in the section's colours with a few simple
     * shapes (a pitch, a court, transfer arrows), so the pages look filled without using anyone
     * else's photographs.
     */
    private function photo(ImageManager $images, UploadMedia $uploadMedia, User $uploader, Category $category, int $index): Media
    {
        [$dark, $bright, $shapes] = self::ARTWORK[$category->slug] ?? self::ARTWORK['podosfairo'];

        $tempFile = tempnam(sys_get_temp_dir(), 'demo');
        $path = $tempFile.'.jpg';

        $this->artwork($images, $dark, $bright, $shapes, $index)
            ->encode(new JpegEncoder(quality: 80))
            ->save($path);

        $media = $uploadMedia->handle(
            new UploadedFile($path, "demo-photo-{$index}.jpg", 'image/jpeg', null, true),
            MediaCollection::FeaturedImage,
            $uploader,
            "Γραφικό επίδειξης: {$category->name}",
        );

        // Run it now rather than waiting for a queue worker, so the demo pages have srcsets immediately.
        (new GenerateMediaVariantsJob($media))->handle($images);

        @unlink($path);
        @unlink($tempFile);

        return $media->refresh();
    }

    /**
     * Draw the gradient and the section's shapes; $index only shifts them so photos are not identical.
     */
    private function artwork(ImageManager $images, string $dark, string $bright, string $shapes, int $index): ImageInterface
    {
        $image = $images->createImage(self::PHOTO_WIDTH, self::PHOTO_HEIGHT);
        $shift = ($index % 5) * 40 - 80;

        for ($y = 0; $y < self::PHOTO_HEIGHT; $y += 10) {
            $band = $this->mix($dark, $bright, $y / self::PHOTO_HEIGHT * 0.75);
            $image->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(self::PHOTO_WIDTH, 10)->at(0, $y)->background($band));
        }

        $line = 'rgba(255, 255, 255, 0.16)';
        $faint = 'rgba(255, 255, 255, 0.09)';

        match ($shapes) {
            'court' => $image
                ->drawCircle(fn (CircleFactory $circle) => $circle->at(1220 + $shift, 760)->radius(380)->border($line, 8))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(300, 180)->at(1070 + $shift, 580)->border($faint, 6))
                ->drawCircle(fn (CircleFactory $circle) => $circle->at(1220 + $shift, 620)->radius(70)->border($line, 6)),
            'arrows' => $image
                ->drawPolygon(fn (PolygonFactory $polygon) => $polygon->point(980 + $shift, 300)->point(1240 + $shift, 300)->point(1240 + $shift, 210)->point(1420 + $shift, 360)->point(1240 + $shift, 510)->point(1240 + $shift, 420)->point(980 + $shift, 420)->background($faint))
                ->drawPolygon(fn (PolygonFactory $polygon) => $polygon->point(620, 500)->point(360, 500)->point(360, 410)->point(180, 560)->point(360, 710)->point(360, 620)->point(620, 620)->background($line)),
            'bars' => $image
                ->drawCircle(fn (CircleFactory $circle) => $circle->at(1250 + $shift, 300)->radius(180)->background($faint))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(520, 18)->at(140, 620)->background($line))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(360, 18)->at(140, 680)->background($faint))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(240, 18)->at(140, 740)->background($faint)),
            default => $image
                ->drawCircle(fn (CircleFactory $circle) => $circle->at(1180 + $shift, 450)->radius(230)->border($line, 6))
                ->drawLine(fn (LineFactory $drawnLine) => $drawnLine->from(1180 + $shift, 0)->to(1180 + $shift, self::PHOTO_HEIGHT)->color($faint)->width(6))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(320, 460)->at(0, 220)->border($line, 6))
                ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(120, 220)->at(0, 340)->border($faint, 6)),
        };

        return $image
            ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(self::PHOTO_WIDTH, 14)->at(0, self::PHOTO_HEIGHT - 14)->background($bright))
            ->drawRectangle(fn (RectangleFactory $rectangle) => $rectangle->size(90 + ($index % 5) * 60, 14)->at(0, self::PHOTO_HEIGHT - 14)->background('#ffffff'));
    }

    /**
     * A colour between two hex colours, for the background gradient.
     */
    private function mix(string $from, string $to, float $ratio): string
    {
        [$fromRed, $fromGreen, $fromBlue] = sscanf($from, '#%02x%02x%02x');
        [$toRed, $toGreen, $toBlue] = sscanf($to, '#%02x%02x%02x');

        return sprintf(
            '#%02x%02x%02x',
            (int) round($fromRed + ($toRed - $fromRed) * $ratio),
            (int) round($fromGreen + ($toGreen - $fromGreen) * $ratio),
            (int) round($fromBlue + ($toBlue - $fromBlue) * $ratio),
        );
    }

    private function title(string $categorySlug, string $home, string $away, int $index): string
    {
        $score = ($index % 4).'-'.(($index + 1) % 3);

        return match ($categorySlug) {
            'basket' => "{$home}: ".['νίκη στην παράταση απέναντι στον '.$away, 'ανατροπή στο τελευταίο δεκάλεπτο με '.$away, 'καθαρή νίκη στο ντέρμπι με '.$away][$index % 3],
            'metagrafes' => 'Μεταγραφές: '.['η '.$home.' κοντά σε συμφωνία για νέο επιθετικό', 'ο '.$away.' ψάχνει λύση στη μεσαία γραμμή', 'πρόταση της '.$home.' για δανεισμό με οψιόν αγοράς'][$index % 3],
            'apopseis' => 'Άποψη: '.['τι έδειξε η '.$home.' στο πρώτο μισό της σεζόν', 'γιατί ο '.$away.' πληρώνει τις απουσίες', 'η αξία της συνέχειας στον πάγκο'][$index % 3],
            default => "{$home} – {$away} {$score}: ".['ανατροπή στο τελευταίο λεπτό', 'μοιρασιά στο ντέρμπι της αγωνιστικής', 'επιβλητική εμφάνιση μπροστά στον κόσμο της', 'κρίσιμο τρίποντο με ανατροπή στο φινάλε', 'δίκαιη ισοπαλία σε ένα ματς χωρίς νικητή'][$index % 5],
        };
    }

    private function body(int $index): string
    {
        $paragraphs = collect(range(0, 4))->map(fn (int $offset) => self::PARAGRAPHS[($index + $offset) % count(self::PARAGRAPHS)]);

        return '<p>'.$paragraphs[0].'</p><p>'.$paragraphs[1].'</p>'
            .'<h2>Τα βασικά σημεία</h2><ul><li>'.$paragraphs[2].'</li><li>'.$paragraphs[3].'</li></ul>'
            .'<blockquote>«Συνεχίζουμε με την ίδια διάθεση και σεβασμό για κάθε αντίπαλο.»</blockquote>'
            .'<p>'.$paragraphs[4].'</p>';
    }
}
