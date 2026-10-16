<?php

use App\Http\Controllers\ArticlePreviewController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public category & article pages
|--------------------------------------------------------------------------
|
| These catch-all routes must be registered LAST (see the end of web.php),
| otherwise they would shadow application routes such as /login or
| /admin/articles. Category slugs can never be one of the reserved words in
| config/reserved-slugs.php, and slugs never contain dots, so asset paths
| such as /robots.txt or Livewire's livewire.js are not matched either.
|
*/

$slugPattern = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('teams/{slug}', [TeamController::class, 'show'])->where('slug', $slugPattern)->name('teams.show');

Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemaps.index');
Route::get('sitemap-news.xml', [SitemapController::class, 'news'])->name('sitemaps.news');
Route::get('sitemaps/{name}.xml', [SitemapController::class, 'show'])
    ->where('name', 'static|articles-[1-9][0-9]*')
    ->name('sitemaps.show');

Route::view('search', 'site.search')->middleware('throttle:search')->name('search');
Route::get('search/suggestions', SearchSuggestionController::class)
    ->middleware('throttle:search-suggestions')
    ->name('search.suggestions');

Route::get('fixtures/{fixture}', [FixtureController::class, 'show'])->whereNumber('fixture')->name('fixtures.show');

Route::get('preview/articles/{article}', ArticlePreviewController::class)
    ->whereNumber('article')
    ->middleware('signed')
    ->name('articles.preview');

Route::get('rss', FeedController::class)->name('feed');

Route::get('news', [NewsController::class, 'index'])->name('news.index');

Route::get('tags/{slug}', [TagController::class, 'show'])->where('slug', $slugPattern)->name('tags.show');

Route::get('authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('authors/{slug}', [AuthorController::class, 'show'])->where('slug', $slugPattern)->name('authors.show');

Route::get('leagues', [LeagueController::class, 'index'])->name('leagues.index');
Route::get('leagues/{slug}', [LeagueController::class, 'show'])->where('slug', $slugPattern)->name('leagues.show');

Route::get('{category}', [CategoryController::class, 'show'])
    ->where('category', $slugPattern)
    ->name('categories.show');

Route::get('{category}/{slug}', [CategoryController::class, 'showPath'])
    ->where(['category' => $slugPattern, 'slug' => $slugPattern])
    ->name('categories.path');
