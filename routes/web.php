<?php

use App\Http\Controllers\HomeController;
use App\Livewire\Admin\Articles\Form as ArticleForm;
use App\Livewire\Admin\Articles\Index as ArticleIndex;
use App\Livewire\Admin\Authors;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Comments as CommentModeration;
use App\Livewire\Admin\Fixtures;
use App\Livewire\Admin\Leagues;
use App\Livewire\Admin\Seasons;
use App\Livewire\Admin\Sports;
use App\Livewire\Admin\Tags;
use App\Livewire\Admin\Teams;
use App\Livewire\Admin\Users;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:admin,editor,author'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('articles', ArticleIndex::class)->name('articles.index');
    Route::livewire('articles/create', ArticleForm::class)->name('articles.create');
    Route::livewire('articles/{article}/edit', ArticleForm::class)->name('articles.edit');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('users', Users::class)->name('users.index');
    Route::livewire('sports', Sports::class)->name('sports.index');
});

Route::middleware(['auth', 'verified', 'role:admin,editor'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('categories', Categories::class)->name('categories.index');
    Route::livewire('tags', Tags::class)->name('tags.index');
    Route::livewire('authors', Authors::class)->name('authors.index');
    Route::livewire('teams', Teams::class)->name('teams.index');
    Route::livewire('leagues', Leagues::class)->name('leagues.index');
    Route::livewire('seasons', Seasons::class)->name('seasons.index');
    Route::livewire('fixtures', Fixtures::class)->name('fixtures.index');
    Route::livewire('comments', CommentModeration::class)->name('comments.index');
});

require __DIR__.'/settings.php';

// Must stay last: public /{category} and /{category}/{slug} catch-all pages.
require __DIR__.'/site.php';
