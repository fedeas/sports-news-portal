<?php

namespace App\Livewire;

use App\Actions\RefreshLiveScores;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Public live scores, read only from the cache filled by RefreshLiveScores.
 *
 * Rendering and polling never query the fixtures table or call the sports data provider.
 */
class LiveScores extends Component
{
    /**
     * Show this one fixture (the fixture page) instead of the strip of today's matches.
     */
    #[Locked]
    public ?int $fixtureId = null;

    /**
     * The fixture as rendered by the page, shown until it appears in the live cache.
     *
     * @var array<string, mixed>|null
     */
    #[Locked]
    public ?array $initial = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function fixtures(): array
    {
        $cached = Cache::get(RefreshLiveScores::CACHE_KEY)['fixtures'] ?? [];

        if ($this->fixtureId === null) {
            return $cached;
        }

        $fixture = collect($cached)->firstWhere('id', $this->fixtureId) ?? $this->initial;

        return $fixture === null ? [] : [$fixture];
    }

    public function render(): View
    {
        return view($this->fixtureId === null ? 'livewire.live-scores' : 'livewire.live-score');
    }
}
