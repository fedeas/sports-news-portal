<?php

namespace App\Jobs;

use App\Actions\RefreshLiveScores;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshLiveScoresJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Keep the live score cache current (e.g. when the day changes), with or without a provider.
     */
    public function handle(RefreshLiveScores $refreshLiveScores): void
    {
        $refreshLiveScores->handle();
    }
}
