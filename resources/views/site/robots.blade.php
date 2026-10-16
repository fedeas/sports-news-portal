User-agent: *
@if ($allowIndexing)
Disallow: /admin
Disallow: /dashboard
Disallow: /settings
Disallow: /login
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /two-factor-challenge

Sitemap: {{ route('sitemaps.index') }}
Sitemap: {{ route('sitemaps.news') }}
@else
Disallow: /
@endif
