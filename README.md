# Sports News Portal

A Greek-language sports news site for Cyprus and Greece, built with Laravel, Livewire and Tailwind CSS. It has a public front end for readers and an admin panel where editors write and publish articles and manage teams, leagues, fixtures and standings.

The site name, sections and competitions are configuration and seed data, so the same code can run any sports publication.

## Features

### For readers

- **Front page** with a main story, the next four stories beside it, a running feed and a block per section.
- **Sections and subsections** (Football → Cyprus, Greece, England…), each with its own black-and-white pictogram and a row of subsections to switch between.
- **Article pages** with responsive images, bylines and co-authors, tags, related stories, share links and comments.
- **News feed** of every story, newest first, grouped by day.
- **Search bar** in the masthead that suggests stories and clubs as you type, with keyboard navigation, and a full results page that also works without JavaScript.
- **Teams, leagues and fixtures**: league tables, upcoming matches, results and live scores refreshed from the cache.
- **Writers' pages**, topic pages, most-read lists and an RSS feed.
- **Mobile first**, with dark mode and accessible markup (landmarks, focus states, skip link, alt text).

### For editors

- Articles with a rich-text editor, scheduled publishing, drafts and signed preview links that expire.
- Sections, tags, writers, sports, teams, leagues, seasons and fixtures, each behind role-based permissions (admin, editor, author).
- Image uploads processed into WebP sizes from 480 to 2000 px.
- Comment moderation.

### Under the hood

- **Caching**: homepage blocks, navigation, standings and fixtures are cached in Redis by tag and cleared when the underlying content changes. Only IDs are cached, so an unpublished article can never be served from a stale entry.
- **Security**: Content Security Policy with per-request nonces, article HTML sanitised on save with an allow-list, rate limits on login, search, suggestions and comments, trusted hosts in production, and authorisation policies on every write.
- **SEO**: unique titles and descriptions, canonical URLs, Open Graph and Twitter cards, `NewsArticle` structured data, XML and news sitemaps, and 301 redirects from old slugs.
- **Performance**: eager loading everywhere (lazy loading is an error outside production), paginated lists, buffered view counts and MySQL full-text search.

## Stack

- PHP 8.4, Laravel 13, Livewire 4, Flux UI, Fortify
- Tailwind CSS 4, Vite
- MySQL 8, Redis (cache, queues, sessions)
- Pest for tests, Pint for formatting

## Requirements

- PHP 8.4 with the `gd`, `intl`, `pdo_mysql` and `mbstring` extensions
- Composer 2
- Node.js 22
- MySQL 8
- Redis 7 (or any compatible server)

## Getting started

```bash
git clone https://github.com/<your-username>/sports-news-portal.git
cd sports-news-portal
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database, set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, then:

```bash
php artisan migrate --seed
php artisan storage:link
composer run dev
```

`composer run dev` starts the web server, the queue worker, the scheduler and Vite together. The site is then at http://localhost:8000.

### Demo content

The default seeder creates the site's sections and competitions and an admin account. To fill the site with demo clubs, fixtures and articles (with generated artwork):

```bash
php artisan db:seed --class=DemoTeamsSeeder
php artisan db:seed --class=DemoContentSeeder
```

Demo content is never seeded in production, and the article seeder does nothing if articles already exist.

### Admin

Sign in at `/login` with `test@example.com` / `password`, then open `/admin/articles`. Change this password before putting the site anywhere public.

## Tests

The suite runs on an in-memory SQLite database, so it needs no database server:

```bash
php artisan test
```

Check formatting with `composer lint:check`, or fix it with `composer lint`.

## Scheduled jobs

The scheduler (`php artisan schedule:work` locally, a cron entry in production) runs:

| Job | Every |
| --- | --- |
| Publish scheduled articles | minute |
| Refresh live scores from the cache | minute |
| Write buffered view counts | minute |
| Generate sitemaps | 15 minutes |
| Sync fixtures from the data provider | day, at 04:00 (only with a provider) |
| Sync scores from the data provider | 5 minutes, configurable (only with a provider) |

## Sports data

All calls to an external sports data API go through `app/Services/SportsData`, with timeouts, retries and a fallback when the provider is down. Set `SPORTS_DATA_PROVIDER` and the provider's key in `.env` to enable imports; leave it empty to manage fixtures by hand from the admin.

## Useful commands

```bash
php artisan media:regenerate-variants   # rebuild image sizes after changing them
php artisan db:seed --class=SiteStructureSeeder   # add new sections or competitions
```

## License

Released under the [MIT License](LICENSE).
