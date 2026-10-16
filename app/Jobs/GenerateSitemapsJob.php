<?php

namespace App\Jobs;

use App\Actions\GenerateSitemaps;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSitemapsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Regenerate every sitemap file.
     */
    public function handle(GenerateSitemaps $generateSitemaps): void
    {
        $generateSitemaps->handle();
    }
}
