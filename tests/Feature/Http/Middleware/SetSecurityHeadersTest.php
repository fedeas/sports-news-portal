<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The directives of a response's Content-Security-Policy header, keyed by name.
 *
 * @return array<string, string>
 */
function cspDirectives(TestResponse $response): array
{
    return collect(explode(';', (string) $response->headers->get('Content-Security-Policy')))
        ->map(fn (string $directive) => explode(' ', trim($directive), 2))
        ->mapWithKeys(fn (array $parts) => [$parts[0] => $parts[1] ?? ''])
        ->all();
}

it('sends the browser security headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('restricts scripts to this origin and a fresh nonce per response', function () {
    $first = cspDirectives($this->get(route('home')));
    $second = cspDirectives($this->get(route('home')));

    expect($first['script-src'])->toMatch("/^'self' 'nonce-[A-Za-z0-9]{40}' 'unsafe-eval'$/")
        ->not->toContain("'unsafe-inline'")
        ->and($first['script-src'])->not->toBe($second['script-src'])
        ->and($first['object-src'])->toBe("'none'")
        ->and($first['frame-ancestors'])->toBe("'self'")
        ->and($first['base-uri'])->toBe("'self'")
        ->and($first['form-action'])->toBe("'self'");
});

it('gives every inline script on the page the response nonce', function (string $page) {
    Livewire::flushState();

    $response = match ($page) {
        'homepage' => $this->get(route('home')),
        'article' => $this->get(Article::factory()->published()->create()->url()),
        'login' => $this->get(route('login')),
        'admin' => $this->actingAs(User::factory()->admin()->create())->get(route('admin.articles.index')),
    };
    preg_match("/'nonce-([^']+)'/", cspDirectives($response)['script-src'], $nonce);
    preg_match_all('/<script\b([^>]*)>/i', $response->getContent(), $scripts);

    $blocked = collect($scripts[1])
        ->reject(fn (string $attributes) => str_contains($attributes, 'type="application/ld+json"'))
        ->reject(fn (string $attributes) => str_contains($attributes, "nonce=\"{$nonce[1]}\""))
        ->values()
        ->all();

    expect($scripts[1])->not->toBeEmpty()
        ->and($blocked)->toBe([]);
})->with(['homepage', 'article', 'login', 'admin']);

it('adds HSTS to secure production responses', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('keeps a header a response already set', function () {
    Route::get('/_test/embeddable', fn () => response('ok')->header('X-Frame-Options', 'DENY'))->middleware('web');

    $this->get('/_test/embeddable')->assertHeader('X-Frame-Options', 'DENY');
});

it('allows images from a media host on another origin', function () {
    config(['filesystems.disks.public.url' => 'https://media.example.com/storage']);

    expect(cspDirectives($this->get(route('home')))['img-src'])->toBe("'self' https://media.example.com data: blob:");
});

it('allows the local Vite dev server, by scheme when it listens on an IPv6 address', function (string $devServer, array $expected) {
    app()->detectEnvironment(fn () => 'local');
    $hotFile = tempnam(sys_get_temp_dir(), 'hot');
    file_put_contents($hotFile, $devServer);
    Vite::useHotFile($hotFile);

    $directives = cspDirectives($this->get(route('home')));

    unlink($hotFile);

    expect(collect(['style-src', 'font-src', 'connect-src'])->mapWithKeys(fn (string $name) => [$name => $directives[$name]])->all())
        ->toBe($expected);
})->with([
    'IPv4 host' => ['http://127.0.0.1:5173', [
        'style-src' => "'self' 'unsafe-inline' http://127.0.0.1:5173",
        'font-src' => "'self' data: http://127.0.0.1:5173",
        'connect-src' => "'self' http://127.0.0.1:5173 ws://127.0.0.1:5173",
    ]],
    'IPv6 host' => ['http://[::1]:5173', [
        'style-src' => "'self' 'unsafe-inline' http:",
        'font-src' => "'self' data: http:",
        'connect-src' => "'self' http: ws:",
    ]],
]);
