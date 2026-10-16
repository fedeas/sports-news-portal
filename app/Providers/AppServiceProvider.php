<?php

namespace App\Providers;

use App\Actions\BuildNavigation;
use App\Actions\BuildSidebar;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Media;
use App\Models\Sport;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, fn (): ImageManager => new ImageManager(
            Driver::class,
            strip: true,
            decodeAnimation: false,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configureViewComposers();
        $this->configureRateLimiting();
        $this->configureLivewire();
        $this->configureTrustedProxies();
        $this->configureDevCommands();
    }

    /**
     * Run the scheduler, and any local services from `app.dev_services`, alongside the server, queue
     * and Vite in `composer run dev`, so scheduled publishing, live scores, sitemaps and view counts
     * work locally.
     */
    protected function configureDevCommands(): void
    {
        DevCommands::artisan('schedule:work', 'scheduler');

        foreach (array_filter(config('app.dev_services', [])) as $name => $command) {
            DevCommands::register($command, $name);
        }
    }

    /**
     * Trust the proxies from `app.trusted_proxies`, so client IPs and HTTPS are read from forwarded headers.
     */
    protected function configureTrustedProxies(): void
    {
        $proxies = config('app.trusted_proxies');

        if (filled($proxies)) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }

    /**
     * Re-run the role check of the page route on every later Livewire request,
     * so a user who loses access cannot keep driving a component that was already on screen.
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([EnsureUserHasRole::class]);
    }

    /**
     * Rate limits for public endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        // The search bar asks once per pause in typing, so it gets more room than the results page.
        RateLimiter::for('search-suggestions', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }

    /**
     * Share data needed by layout partials on every public page.
     */
    protected function configureViewComposers(): void
    {
        View::composer('partials.site-header', function (\Illuminate\View\View $view) {
            $view->with('navigation', app(BuildNavigation::class)->handle());
        });

        View::composer('partials.site-rail', function (\Illuminate\View\View $view) {
            $view->with(app(BuildSidebar::class)->handle());
        });
    }

    /**
     * Store short, stable aliases in polymorphic `*_type` columns instead of class names.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'article' => Article::class,
            'author' => Author::class,
            'category' => Category::class,
            'league' => League::class,
            'media' => Media::class,
            'sport' => Sport::class,
            'tag' => Tag::class,
            'team' => Team::class,
            'user' => User::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
