<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use XMLWriter;

class FeedController extends Controller
{
    public const ITEMS = 30;

    /**
     * The RSS feed of the latest articles, at /rss.
     */
    public function __invoke(): Response
    {
        $articles = Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(['category.parent', 'author'])
            ->latest('published_at')
            ->limit(self::ITEMS)
            ->get();

        return response($this->feed($articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, Article>  $articles
     */
    private function feed($articles): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->startElement('channel');

        $xml->writeElement('title', (string) config('app.name'));
        $xml->writeElement('link', route('home'));
        $xml->writeElement('description', __('Sports news from Cyprus and Greece: football, basketball, results and live scores.'));
        $xml->writeElement('language', (string) config('app.locale'));
        $xml->writeElement('lastBuildDate', ($articles->first()?->published_at ?? now())->toRfc2822String());

        $xml->startElement('atom:link');
        $xml->writeAttribute('href', route('feed'));
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($articles as $article) {
            $xml->startElement('item');
            $xml->writeElement('title', $article->title);
            $xml->writeElement('link', $article->url());
            $xml->writeElement('description', $article->metaDescription());
            $xml->writeElement('category', $article->category->name);
            $xml->writeElement('author', $article->author->name);
            $xml->writeElement('pubDate', $article->published_at->toRfc2822String());

            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'true');
            $xml->text($article->url());
            $xml->endElement();

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
