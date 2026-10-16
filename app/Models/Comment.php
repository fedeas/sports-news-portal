<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A reader comment (guest) or a staff reply. Guest emails are never shown publicly.
 *
 * @property int $id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int|null $parent_id
 * @property int|null $user_id
 * @property string|null $guest_name
 * @property string|null $guest_email
 * @property string $body Plain text; always escaped when shown
 * @property CommentStatus $status
 * @property string|null $ip_address
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['guest_name', 'guest_email', 'body'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Comments visible to readers.
     *
     * @param  Builder<Comment>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', CommentStatus::Approved);
    }

    /**
     * The name shown next to the comment.
     */
    public function authorName(): string
    {
        return $this->user->author->name ?? $this->user->name ?? (string) $this->guest_name;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Replies readers can see, oldest first, as a conversation reads.
     *
     * @return HasMany<Comment, $this>
     */
    public function approvedReplies(): HasMany
    {
        return $this->replies()->approved()->oldest();
    }

    /**
     * The staff account that wrote the comment; null for guests.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
