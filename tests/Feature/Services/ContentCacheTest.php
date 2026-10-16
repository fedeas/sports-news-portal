<?php

use App\Services\ContentCache;

it('serves a remembered value until one of its tags is flushed', function () {
    $cache = app(ContentCache::class);
    $calls = 0;
    $compute = function () use (&$calls) {
        return ++$calls;
    };

    $first = $cache->remember([ContentCache::ARTICLES, ContentCache::CATEGORIES], 'probe', $compute);
    $second = $cache->remember([ContentCache::ARTICLES, ContentCache::CATEGORIES], 'probe', $compute);
    $cache->flush([ContentCache::CATEGORIES]);
    $afterFlush = $cache->remember([ContentCache::ARTICLES, ContentCache::CATEGORIES], 'probe', $compute);

    expect([$first, $second, $afterFlush])->toBe([1, 1, 2]);
});

it('keeps values whose tags were not flushed', function () {
    $cache = app(ContentCache::class);
    $cache->remember([ContentCache::FIXTURES], 'probe', fn () => 'fixtures');

    $cache->flush([ContentCache::ARTICLES]);

    expect($cache->remember([ContentCache::FIXTURES], 'probe', fn () => 'recomputed'))->toBe('fixtures');
});

it('runs the callback every time when the store cannot tag values', function () {
    config(['cache.default' => 'file']);
    $cache = app(ContentCache::class);
    $calls = 0;

    $cache->remember([ContentCache::ARTICLES], 'probe', function () use (&$calls) {
        return ++$calls;
    });
    $result = $cache->remember([ContentCache::ARTICLES], 'probe', function () use (&$calls) {
        return ++$calls;
    });
    $cache->flush([ContentCache::ARTICLES]);

    expect($result)->toBe(2);
});
