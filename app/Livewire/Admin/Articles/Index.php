<?php

namespace App\Livewire\Admin\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Articles')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Article::class);
    }

    /**
     * Go back to the first page whenever a filter changes.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Move an article to the trash.
     */
    public function delete(int $articleId): void
    {
        $article = Article::query()->with('author')->findOrFail($articleId);

        $this->authorize('delete', $article);

        $article->delete();

        Flux::toast(variant: 'success', text: __('Article deleted.'));
    }

    /**
     * Latest edited articles; authors only see their own.
     *
     * @return LengthAwarePaginator<int, Article>
     */
    #[Computed]
    public function articles(): LengthAwarePaginator
    {
        $user = Auth::user();
        $search = trim($this->search);

        return Article::query()
            ->with(['category', 'author'])
            ->when(! $user->isEditorOrAbove(), fn (Builder $query) => $query->ownedBy($user))
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->when(ArticleStatus::tryFrom($this->status), fn (Builder $query, ArticleStatus $status) => $query->where('status', $status))
            ->latest('updated_at')
            ->paginate(20);
    }

    /**
     * @return array<int, ArticleStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return ArticleStatus::cases();
    }

    public function render(): View
    {
        return view('livewire.admin.articles.index');
    }
}
