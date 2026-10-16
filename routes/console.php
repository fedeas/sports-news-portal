<?php

use App\Jobs\FlushArticleViewsJob;
use App\Jobs\GenerateSitemapsJob;
use App\Jobs\PublishScheduledArticlesJob;
use App\Jobs\RefreshLiveScoresJob;
use App\Jobs\SyncFixturesJob;
use App\Jobs\SyncScoresJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PublishScheduledArticlesJob)->everyMinute()->withoutOverlapping();

Schedule::job(new RefreshLiveScoresJob)->everyMinute()->withoutOverlapping();

Schedule::job(new FlushArticleViewsJob)->everyMinute()->withoutOverlapping();

Schedule::job(new GenerateSitemapsJob)->everyFifteenMinutes()->withoutOverlapping();

Schedule::job(new SyncFixturesJob)
    ->dailyAt('04:00')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping()
    ->when(fn () => filled(config('sports-data.provider')));

Schedule::job(new SyncScoresJob)
    ->cron('*/'.max(1, (int) config('sports-data.scores_interval_minutes')).' * * * *')
    ->withoutOverlapping()
    ->when(fn () => filled(config('sports-data.provider')));
