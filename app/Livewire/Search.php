<?php

namespace App\Livewire;

use App\Actions\SearchArticles;
use App\Actions\SearchTeams;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Search extends Component
{
    use WithPagination;

    public const MIN_LENGTH = 2;

    public const ARTICLES_PER_PAGE = 20;

    public const TEAMS_LIMIT = 6;

    /**
     * Searches allowed per visitor (IP address) per minute, including result pages.
     */
    public const SEARCHES_PER_MINUTE = 30;

    #[Url(as: 'q', except: '')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    /**
     * The results for the current term, null while the term is too short.
     *
     * @return array{rateLimited: bool, articles?: LengthAwarePaginator, teams?: Collection<int, Team>}|null
     */
    #[Computed]
    public function results(): ?array
    {
        $term = Str::of($this->query)->squish()->limit(SearchArticles::MAX_LENGTH, '')->value();

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return null;
        }

        $rateLimitKey = 'search:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::SEARCHES_PER_MINUTE)) {
            return ['rateLimited' => true];
        }

        RateLimiter::hit($rateLimitKey, 60);

        return [
            'rateLimited' => false,
            'articles' => app(SearchArticles::class)->handle($term)->paginate(self::ARTICLES_PER_PAGE),
            'teams' => app(SearchTeams::class)->handle($term)
                ->with('logo')
                ->limit(self::TEAMS_LIMIT)
                ->get(),
        ];
    }

    public function render(): View
    {
        return view('livewire.search');
    }
}
