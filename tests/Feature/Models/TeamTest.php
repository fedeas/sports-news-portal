<?php

use App\Models\Article;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('removes its article links when deleted', function () {
    $team = Team::factory()->create();
    $article = Article::factory()->create();
    $team->articles()->attach($article);

    $team->delete();

    expect(DB::table('article_subjects')->count())->toBe(0)
        ->and($article->refresh()->exists)->toBeTrue();
});
