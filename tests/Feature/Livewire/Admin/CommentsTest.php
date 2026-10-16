<?php

use App\Enums\CommentStatus;
use App\Livewire\Admin\Comments;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.comments.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.comments.index'))
            ->assertForbidden();
    });

    it('shows pending comments to editors by default, escaped', function () {
        Comment::factory()->create(['body' => '<script>alert(1)</script> σε αναμονή']);
        Comment::factory()->approved()->create(['body' => 'Ήδη εγκεκριμένο']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.comments.index'))
            ->assertOk()
            ->assertSee('σε αναμονή')
            ->assertDontSee('Ήδη εγκεκριμένο')
            ->assertDontSee('<script>alert(1)</script>', false);
    });
});

describe('moderating', function () {
    it('approves a comment and records who approved it', function () {
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor);
        $comment = Comment::factory()->create();

        Livewire::test(Comments::class)->call('moderate', $comment->id, 'approved');

        $comment->refresh();

        expect($comment->status)->toBe(CommentStatus::Approved)
            ->and($comment->approved_by)->toBe($editor->id)
            ->and($comment->approved_at)->not->toBeNull();
    });

    it('hides an approved comment again when it is rejected or marked as spam', function (string $status) {
        $this->actingAs(User::factory()->editor()->create());
        $comment = Comment::factory()->approved()->create();

        Livewire::test(Comments::class)->call('moderate', $comment->id, $status);

        expect($comment->refresh()->status)->toBe(CommentStatus::from($status))
            ->and($comment->approved_at)->toBeNull();
    })->with(['spam', 'rejected']);

    it('rejects an unknown status', function () {
        $this->actingAs(User::factory()->editor()->create());
        $comment = Comment::factory()->create();

        Livewire::test(Comments::class)
            ->call('moderate', $comment->id, 'featured')
            ->assertHasErrors(['status']);

        expect($comment->refresh()->status)->toBe(CommentStatus::Pending);
    });

    it('deletes a comment with its replies', function () {
        $this->actingAs(User::factory()->editor()->create());
        $comment = Comment::factory()->approved()->create();
        $reply = Comment::factory()->replyTo($comment)->create();

        Livewire::test(Comments::class)->call('delete', $comment->id);

        expect(Comment::query()->whereKey([$comment->id, $reply->id])->count())->toBe(0);
    });
});
