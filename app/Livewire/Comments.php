<?php

namespace App\Livewire;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Reader comments under an article. Guests post for moderation; logged-in staff post directly.
 */
class Comments extends Component
{
    use WithPagination;

    public const PER_PAGE = 20;

    public const MAX_COMMENTS_PER_WINDOW = 5;

    public const RATE_LIMIT_WINDOW_SECONDS = 600;

    #[Locked]
    public int $articleId;

    public string $name = '';

    public string $email = '';

    public string $body = '';

    /**
     * Honeypot: hidden from people, filled in by spam bots.
     */
    public string $website = '';

    /**
     * The comment being replied to. Comes from the browser, so it is validated on submit.
     */
    public ?int $replyTo = null;

    public ?string $notice = null;

    public function reply(int $commentId): void
    {
        $this->replyTo = $commentId;
        $this->notice = null;
    }

    public function cancelReply(): void
    {
        $this->replyTo = null;
    }

    /**
     * Post a comment or a reply to an approved top-level comment on this article.
     */
    public function submit(): void
    {
        $article = Article::query()->published()->findOrFail($this->articleId);
        $staff = Auth::user();

        $rules = [
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'replyTo' => [
                'nullable', 'integer',
                Rule::exists(Comment::class, 'id')
                    ->where('commentable_type', $article->getMorphClass())
                    ->where('commentable_id', $article->id)
                    ->whereNull('parent_id')
                    ->where('status', CommentStatus::Approved->value),
            ],
        ];

        if ($staff === null) {
            $rules['name'] = ['required', 'string', 'max:60'];
            $rules['email'] = ['required', 'email', 'max:150'];
        }

        $validated = $this->validate($rules, [
            'replyTo.exists' => __('You can only reply to a published comment on this article.'),
        ]);

        // Bots fill the hidden field: behave as if it worked, but store nothing.
        if ($this->website !== '') {
            $this->finish(__('Thank you! Your comment will appear after moderation.'));

            return;
        }

        $rateLimitKey = 'comment:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_COMMENTS_PER_WINDOW)) {
            $this->addError('body', __('You are commenting too often. Please try again in a few minutes.'));

            return;
        }

        RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_WINDOW_SECONDS);

        $comment = new Comment([
            'body' => $validated['body'],
            'guest_name' => $staff === null ? $validated['name'] : null,
            'guest_email' => $staff === null ? $validated['email'] : null,
        ]);
        $comment->commentable()->associate($article);
        $comment->parent_id = $validated['replyTo'] ?? null;
        $comment->ip_address = request()->ip();

        if ($staff !== null) {
            $comment->user()->associate($staff);
            $comment->approver()->associate($staff);
            $comment->status = CommentStatus::Approved;
            $comment->approved_at = now();
        } else {
            $comment->status = CommentStatus::Pending;
        }

        $comment->save();

        $this->finish($staff !== null
            ? __('Your comment has been published.')
            : __('Thank you! Your comment will appear after moderation.'));
    }

    /**
     * Approved top-level comments, newest first, each with its approved replies, oldest first.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        return Comment::query()
            ->approved()
            ->where('commentable_type', (new Article)->getMorphClass())
            ->where('commentable_id', $this->articleId)
            ->whereNull('parent_id')
            ->with([
                'user.author',
                'approvedReplies.user.author',
            ])
            ->latest()
            ->paginate(self::PER_PAGE, pageName: 'comments');
    }

    public function render(): View
    {
        return view('livewire.comments');
    }

    private function finish(string $notice): void
    {
        $this->reset('body', 'website', 'replyTo');
        $this->resetValidation();
        $this->notice = $notice;
    }
}
