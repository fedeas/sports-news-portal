<?php

namespace App\Actions;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use XMLWriter;

class GenerateSitemaps
{
    public const DIRECTORY = 'sitemaps';

    public const URLS_PER_FILE = 10_000;

    /**
     * Google News only reads articles from the last two days.
     */
    public const NEWS_WINDOW_HOURS = 48;

    public const NEWS_LIMIT = 1000;

    /**
     * Write the sitemap index, the static and article sitemaps and the news sitemap to local storage.
     *
     * Every run rewrites all files and removes article files left over from a larger previous run,
     * so it is safe to run as often as needed.
     */
    public function handle(int $urlsPerFile = self::URLS_PER_FILE): void
    {
        $disk = Storage::disk('local');
        $sitemaps = ['static.xml'];

        $disk->put(self::DIRECTORY.'/static.xml', $this->urlSet($this->staticEntries()));

        $articleFiles = 0;

        $this->liveArticles()->chunkById($urlsPerFile, function (Collection $articles) use ($disk, &$articleFiles, &$sitemaps) {
            $articleFiles++;
            $sitemaps[] = "articles-{$articleFiles}.xml";

            $disk->put(self::DIRECTORY."/articles-{$articleFiles}.xml", $this->urlSet($articles->map(fn (Article $article) => [
                'loc' => $article->url(),
                'lastmod' => $article->updated_at->toAtomString(),
            ])));
        });

        foreach ($disk->files(self::DIRECTORY) as $path) {
            if (preg_match('/articles-(\d+)\.xml$/', $path, $matches) && (int) $matches[1] > $articleFiles) {
                $disk->delete($path);
            }
        }

        $disk->put(self::DIRECTORY.'/index.xml', $this->index($sitemaps));
        $disk->put(self::DIRECTORY.'/news.xml', $this->urlSet($this->newsEntries(), news: true));
    }

    /**
     * @return Builder<Article>
     */
    private function liveArticles(): Builder
    {
        return Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with('category');
    }

    /**
     * @return array<int, array{loc: string, lastmod?: string}>
     */
    private function staticEntries(): array
    {
        $entries = [
            ['loc' => route('home')],
            ['loc' => route('news.index')],
            ['loc' => route('teams.index')],
            ['loc' => route('leagues.index')],
            ['loc' => route('authors.index')],
        ];

        Category::query()->active()->with('parent')->orderBy('id')->get()
            ->filter(fn (Category $category) => $category->parent_id === null || $category->parent?->is_active)
            ->each(function (Category $category) use (&$entries) {
                $entries[] = ['loc' => $category->url(), 'lastmod' => $category->updated_at->toAtomString()];
            });

        foreach ([Team::class, League::class] as $model) {
            $model::query()->active()->orderBy('id')->get(['id', 'slug', 'updated_at'])
                ->each(function (Team|League $subject) use (&$entries) {
                    $entries[] = ['loc' => $subject->url(), 'lastmod' => $subject->updated_at->toAtomString()];
                });
        }

        Author::query()
            ->where('is_active', true)
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Author $author) use (&$entries) {
                $entries[] = ['loc' => $author->url(), 'lastmod' => $author->updated_at->toAtomString()];
            });

        Tag::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Tag $tag) use (&$entries) {
                $entries[] = ['loc' => $tag->url(), 'lastmod' => $tag->updated_at->toAtomString()];
            });

        return $entries;
    }

    /**
     * @return array<int, array{loc: string, news: array{published_at: string, title: string}}>
     */
    private function newsEntries(): array
    {
        return $this->liveArticles()
            ->where('published_at', '>=', now()->subHours(self::NEWS_WINDOW_HOURS))
            ->latest('published_at')
            ->limit(self::NEWS_LIMIT)
            ->get()
            ->map(fn (Article $article) => [
                'loc' => $article->url(),
                'news' => ['published_at' => $article->published_at->toAtomString(), 'title' => $article->title],
            ])
            ->all();
    }

    /**
     * @param  iterable<array{loc: string, lastmod?: string, news?: array{published_at: string, title: string}}>  $entries
     */
    private function urlSet(iterable $entries, bool $news = false): string
    {
        $xml = $this->document();
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        if ($news) {
            $xml->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
        }

        foreach ($entries as $entry) {
            $xml->startElement('url');
            $xml->writeElement('loc', $entry['loc']);

            if (isset($entry['lastmod'])) {
                $xml->writeElement('lastmod', $entry['lastmod']);
            }

            if (isset($entry['news'])) {
                $xml->startElement('news:news');
                $xml->startElement('news:publication');
                $xml->writeElement('news:name', (string) config('app.name'));
                $xml->writeElement('news:language', (string) config('app.locale'));
                $xml->endElement();
                $xml->writeElement('news:publication_date', $entry['news']['published_at']);
                $xml->writeElement('news:title', $entry['news']['title']);
                $xml->endElement();
            }

            $xml->endElement();
        }

        $xml->endElement();

        return $xml->outputMemory();
    }

    /**
     * @param  array<int, string>  $files
     */
    private function index(array $files): string
    {
        $xml = $this->document();
        $xml->startElement('sitemapindex');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($files as $file) {
            $xml->startElement('sitemap');
            $xml->writeElement('loc', route('sitemaps.show', ['name' => basename($file, '.xml')]));
            $xml->writeElement('lastmod', now()->toAtomString());
            $xml->endElement();
        }

        $xml->endElement();

        return $xml->outputMemory();
    }

    private function document(): XMLWriter
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        return $xml;
    }
}
