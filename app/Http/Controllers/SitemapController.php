<?php

namespace App\Http\Controllers;

use App\Actions\GenerateSitemaps;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    /**
     * The sitemap index at /sitemap.xml.
     */
    public function index(GenerateSitemaps $generateSitemaps): Response
    {
        return $this->serve('index.xml', $generateSitemaps);
    }

    /**
     * The Google News sitemap at /sitemap-news.xml.
     */
    public function news(GenerateSitemaps $generateSitemaps): Response
    {
        return $this->serve('news.xml', $generateSitemaps);
    }

    /**
     * A sitemap listed in the index, e.g. /sitemaps/articles-1.xml.
     */
    public function show(string $name, GenerateSitemaps $generateSitemaps): Response
    {
        return $this->serve("{$name}.xml", $generateSitemaps);
    }

    public function robots(): Response
    {
        return response()
            ->view('site.robots', ['allowIndexing' => app()->isProduction()])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Serve a generated file. Files are generated on demand only when none exist yet, so
     * requests for unknown names cannot trigger repeated regeneration.
     */
    private function serve(string $file, GenerateSitemaps $generateSitemaps): Response
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(GenerateSitemaps::DIRECTORY.'/index.xml')) {
            $generateSitemaps->handle();
        }

        $path = GenerateSitemaps::DIRECTORY.'/'.$file;

        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
