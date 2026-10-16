<?php

namespace App\Http\Controllers;

use App\Actions\BuildHomepage;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Show the public homepage.
     */
    public function __invoke(BuildHomepage $buildHomepage): View
    {
        return view('site.home', [
            ...$buildHomepage->handle(),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => route('home'),
                'inLanguage' => app()->getLocale(),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ]);
    }
}
