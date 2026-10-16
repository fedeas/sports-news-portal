<?php

use App\Enums\CommentStatus;
use App\Livewire\Comments;
use App\Models\Article;
use App\Models\Author;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('comment:127.0.0.1');
});

function commentForm(Article $article): Testable
{
    return Livewire::test(Comments::class, ['articleId' => $article->id])
        ->set('name', 'Νίκος Αναγνώστης')
        ->set('email', 'nikos@example.com')
        ->set('body', 'Πολύ καλό άρθρο!');
}

describe('reading comments', function () {
    it('shows only approved comments and approved replies', function () {
        $article = Article::factory()->published()->create();
        $approved = Comment::factory()->approved()->forArticle($article)->create(['body' => 'Εγκεκριμένο σχόλιο']);
        Comment::factory()->forArticle($article)->create(['body' => 'Σχόλιο σε αναμονή']);
        Comment::factory()->approved()->replyTo($approved)->create(['body' => 'Εγκεκριμένη απάντηση']);
        Comment::factory()->replyTo($approved)->create(['body' => 'Απάντηση σε αναμονή']);
        Comment::factory()->approved()->create(['body' => 'Σχόλιο άλλου άρθρου']);

        Livewire::test(Comments::class, ['articleId' => $article->id])
            ->assertSee('Εγκεκριμένο σχόλιο')
            ->assertSee('Εγκεκριμένη απάντηση')
            ->assertDontSee('Σχόλιο σε αναμονή')
            ->assertDontSee('Απάντηση σε αναμονή')
            ->assertDontSee('Σχόλιο άλλου άρθρου');
    });

    it('escapes names and comment text and never shows guest emails', function () {
        $article = Article::factory()->published()->create();
        Comment::factory()->approved()->forArticle($article)->create([
            'guest_name' => '<b>Όνομα</b>',
            'guest_email' => 'hidden@example.com',
            'body' => '<script>alert(1)</script>',
        ]);

        Livewire::test(Comments::class, ['articleId' => $article->id])
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<b>Όνομα</b>', false)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('hidden@example.com');
    });
});

describe('posting comments', function () {
    it('saves a guest comment for moderation without showing it yet', function () {
        $article = Article::factory()->published()->create();

        commentForm($article)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee(__('Thank you! Your comment will appear after moderation.'))
            ->assertDontSee('Πολύ καλό άρθρο!');

        $comment = Comment::query()->sole();

        expect($comment->status)->toBe(CommentStatus::Pending)
            ->and($comment->guest_name)->toBe('Νίκος Αναγνώστης')
            ->and($comment->guest_email)->toBe('nikos@example.com')
            ->and($comment->ip_address)->toBe('127.0.0.1')
            ->and($comment->commentable_id)->toBe($article->id);
    });

    it('requires a name, an email and a comment from guests', function () {
        $article = Article::factory()->published()->create();

        Livewire::test(Comments::class, ['articleId' => $article->id])
            ->call('submit')
            ->assertHasErrors(['name' => 'required', 'email' => 'required', 'body' => 'required']);
    });

    it('publishes comments from logged-in staff immediately under their byline', function () {
        $user = User::factory()->author()->create();
        Author::factory()->forUser($user)->create(['name' => 'Μαρία Χριστοδούλου']);
        $article = Article::factory()->published()->create();
        $this->actingAs($user);

        Livewire::test(Comments::class, ['articleId' => $article->id])
            ->set('body', 'Ευχαριστούμε για τα σχόλια!')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Μαρία Χριστοδούλου');

        $comment = Comment::query()->sole();

        expect($comment->status)->toBe(CommentStatus::Approved)
            ->and($comment->user_id)->toBe($user->id)
            ->and($comment->guest_email)->toBeNull();
    });

    it('accepts a reply to an approved comment on the same article', function () {
        $article = Article::factory()->published()->create();
        $parent = Comment::factory()->approved()->forArticle($article)->create();

        commentForm($article)
            ->call('reply', $parent->id)
            ->call('submit')
            ->assertHasNoErrors();

        expect(Comment::query()->latest('id')->first()->parent_id)->toBe($parent->id);
    });

    it('rejects replies to comments that cannot be replied to', function (string $case) {
        $article = Article::factory()->published()->create();

        $target = match ($case) {
            'another article' => Comment::factory()->approved()->create(),
            'a pending comment' => Comment::factory()->forArticle($article)->create(),
            'a reply' => Comment::factory()->approved()->replyTo(Comment::factory()->approved()->forArticle($article)->create())->create(),
        };

        commentForm($article)
            ->set('replyTo', $target->id)
            ->call('submit')
            ->assertHasErrors(['replyTo' => 'exists']);

        expect(Comment::query()->where('body', 'Πολύ καλό άρθρο!')->exists())->toBeFalse();
    })->with(['another article', 'a pending comment', 'a reply']);

    it('pretends to accept but does not store comments that fill the honeypot', function () {
        $article = Article::factory()->published()->create();

        commentForm($article)
            ->set('website', 'https://spam.example')
            ->call('submit')
            ->assertSee(__('Thank you! Your comment will appear after moderation.'));

        expect(Comment::query()->count())->toBe(0);
    });

    it('limits how often a visitor can comment', function () {
        $article = Article::factory()->published()->create();
        $component = commentForm($article);

        foreach (range(1, Comments::MAX_COMMENTS_PER_WINDOW) as $attempt) {
            $component->set('body', "Σχόλιο νούμερο {$attempt}")->call('submit')->assertHasNoErrors();
        }

        $component->set('body', 'Ένα σχόλιο παραπάνω')
            ->call('submit')
            ->assertHasErrors(['body']);

        expect(Comment::query()->count())->toBe(Comments::MAX_COMMENTS_PER_WINDOW);
    });

    it('does not let the browser change which article it comments on', function () {
        $article = Article::factory()->published()->create();

        expect(fn () => Livewire::test(Comments::class, ['articleId' => $article->id])->set('articleId', $article->id + 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});
