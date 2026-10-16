<?php

namespace App\Livewire\Admin;

use App\Enums\CommentStatus;
use App\Models\Comment;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Comments')]
class Comments extends Component
{
    use WithPagination;

    /**
     * The status the queue shows; pending comments by default.
     */
    #[Url(except: 'pending')]
    public string $status = 'pending';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Comment::class);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Approve, reject or mark a comment as spam.
     */
    public function moderate(int $commentId, string $status): void
    {
        $comment = Comment::query()->findOrFail($commentId);

        $this->authorize('update', $comment);

        Validator::make(['status' => $status], ['status' => ['required', Rule::enum(CommentStatus::class)]])->validate();

        $comment->status = CommentStatus::from($status);

        if ($comment->status === CommentStatus::Approved) {
            $comment->approved_at = now();
            $comment->approver()->associate(Auth::user());
        } else {
            $comment->approved_at = null;
            $comment->approver()->dissociate();
        }

        $comment->save();

        Flux::toast(variant: 'success', text: __('Comment updated.'));
    }

    public function delete(int $commentId): void
    {
        $comment = Comment::query()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $comment->delete();

        Flux::toast(variant: 'success', text: __('Comment deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        return Comment::query()
            ->with(['commentable.category', 'user.author'])
            ->when(CommentStatus::tryFrom($this->status), fn (Builder $query, CommentStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(30);
    }

    /**
     * @return array<int, CommentStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return CommentStatus::cases();
    }
}
