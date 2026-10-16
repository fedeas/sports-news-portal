<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Send the Content Security Policy and the other browser security headers with every web response.
     *
     * Scripts must come from this origin or carry the per-request nonce that Vite, Livewire and Flux
     * add to their tags. Alpine (bundled with Livewire) evaluates expressions with `new Function()`,
     * so `'unsafe-eval'` stays until the CSP-safe build is adopted. Inline styles are allowed because
     * Flux, Livewire and the self-hosted font faces use them. Locally the policy also allows the Vite
     * dev server and inline scripts injected by development tooling. A separate asset (CDN) or media
     * origin is allowed for the resources served from it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = [
            'Content-Security-Policy' => $this->contentSecurityPolicy($nonce),
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ];

        if ($request->isSecure() && app()->isProduction()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $assetOrigin = $this->origin(config('app.asset_url'));
        $mediaOrigin = $this->origin(Storage::disk('public')->url(''));

        $scripts = ["'self'", $assetOrigin, "'nonce-{$nonce}'", "'unsafe-eval'"];
        $styles = ["'self'", $assetOrigin, "'unsafe-inline'"];
        $connections = ["'self'"];
        $images = ["'self'", $assetOrigin, $mediaOrigin, 'data:', 'blob:'];
        $fonts = ["'self'", $assetOrigin, 'data:'];

        if (app()->isLocal()) {
            $scripts = ["'self'", $assetOrigin, "'unsafe-inline'", "'unsafe-eval'"];

            if (Vite::isRunningHot()) {
                [$devServer, $devSocket] = $this->devServerSources(rtrim((string) file_get_contents(Vite::hotFile())));

                array_push($scripts, $devServer);
                array_push($styles, $devServer);
                array_push($images, $devServer);
                array_push($fonts, $devServer);
                array_push($connections, $devServer, $devSocket);
            }
        }

        return collect([
            'default-src' => ["'self'"],
            'script-src' => $scripts,
            'style-src' => $styles,
            'img-src' => $images,
            'font-src' => $fonts,
            'connect-src' => $connections,
            'media-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ])->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', array_unique(array_filter($sources))))->join('; ');
    }

    /**
     * CSP sources for the Vite dev server and its websocket.
     *
     * CSP cannot name an IPv6 address such as `http://[::1]:5173`, so for one only the schemes are
     * allowed; this applies to local development only.
     *
     * @return array{0: string, 1: string}
     */
    private function devServerSources(string $url): array
    {
        if (str_contains((string) parse_url($url, PHP_URL_HOST), ':')) {
            return ['http:', 'ws:'];
        }

        return [$url, preg_replace('#^http#', 'ws', $url)];
    }

    /**
     * The scheme, host and port of a URL on another origin (a CDN or media host); null for this origin.
     */
    private function origin(?string $url): ?string
    {
        $parts = parse_url((string) $url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return $origin === request()->getSchemeAndHttpHost() ? null : $origin;
    }
}
