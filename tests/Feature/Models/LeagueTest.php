<?php

use App\Models\Article;
use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('removes its article links when deleted', function () {
    $league = League::factory()->create();
    $article = Article::factory()->create();
    $league->articles()->attach($article);

    $league->delete();

    expect(DB::table('article_subjects')->count())->toBe(0)
        ->and($article->refresh()->exists)->toBeTrue();
});
