<?php

namespace App\Http\Controllers;

use App\Actions\RefreshLiveScores;
use App\Models\Fixture;
use Illuminate\Contracts\View\View;

class FixtureController extends Controller
{
    /**
     * Show a match page; its score block updates from the live score cache.
     */
    public function show(Fixture $fixture): View
    {
        $fixture->load(['homeTeam.logo', 'awayTeam.logo', 'season.league']);

        abort_unless($fixture->season->league->is_active, 404);

        return view('site.fixtures.show', [
            'fixture' => $fixture,
            'initial' => RefreshLiveScores::snapshot($fixture),
        ]);
    }
}
