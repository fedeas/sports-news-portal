@php
    $inputClasses = 'mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-base focus:outline-2 focus:outline-offset-2 focus:outline-brand dark:border-zinc-700 dark:bg-zinc-900';
@endphp

<section aria-labelledby="comments-heading" class="mt-10">
    <h2 id="comments-heading" class="section-heading text-xl font-extrabold">
        {{ __('Comments') }}
        <span class="text-base font-semibold text-zinc-500 dark:text-zinc-400">({{ $this->comments->total() }})</span>
    </h2>

    @if ($this->comments->isEmpty())
        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('No comments yet. Be the first to comment.') }}</p>
    @else
        <ol class="mt-6 space-y-6">
            @foreach ($this->comments as $comment)
                <li wire:key="comment-{{ $comment->id }}">
                    @include('livewire.partials.comment', ['comment' => $comment])

                    <button
                        type="button"
                        wire:click="reply({{ $comment->id }})"
                        class="mt-1 rounded text-sm font-semibold text-brand-content hover:underline focus-visible:outline-2 focus-visible:outline-brand"
                    >
                        {{ __('Reply') }}
                    </button>

                    @if ($comment->approvedReplies->isNotEmpty())
                        <ol class="mt-4 space-y-4 border-s-2 border-zinc-200 ps-4 dark:border-zinc-800">
                            @foreach ($comment->approvedReplies as $reply)
                                <li wire:key="comment-{{ $reply->id }}">
                                    @include('livewire.partials.comment', ['comment' => $reply])
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </li>
            @endforeach
        </ol>

        <div class="mt-6">
            {{ $this->comments->links() }}
        </div>
    @endif

    <form wire:submit="submit" class="mt-10 space-y-4 rounded-lg border border-zinc-200 p-4 sm:p-6 dark:border-zinc-800">
        <h3 class="text-lg font-bold">{{ $replyTo ? __('Reply to a comment') : __('Leave a comment') }}</h3>

        @if ($replyTo)
            <button type="button" wire:click="cancelReply" class="text-sm font-semibold text-zinc-600 hover:underline dark:text-zinc-400">
                {{ __('Cancel reply') }}
            </button>
        @endif

        @if ($notice)
            <p role="status" class="rounded-lg bg-green-50 p-3 text-sm text-green-900 dark:bg-green-950 dark:text-green-200">{{ $notice }}</p>
        @endif

        @guest
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="comment-name" class="block text-sm font-medium">{{ __('Name') }}</label>
                    <input id="comment-name" type="text" wire:model="name" maxlength="60" autocomplete="name" required class="{{ $inputClasses }}" @error('name') aria-invalid="true" aria-describedby="comment-name-error" @enderror />
                    @error('name') <p id="comment-name-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="comment-email" class="block text-sm font-medium">{{ __('Email') }}</label>
                    <input id="comment-email" type="email" wire:model="email" maxlength="150" autocomplete="email" required class="{{ $inputClasses }}" aria-describedby="comment-email-hint @error('email') comment-email-error @enderror" @error('email') aria-invalid="true" @enderror />
                    <p id="comment-email-hint" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Your email is never shown.') }}</p>
                    @error('email') <p id="comment-email-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        @endguest

        <div>
            <label for="comment-body" class="block text-sm font-medium">{{ __('Comment') }}</label>
            <textarea id="comment-body" wire:model="body" rows="4" maxlength="2000" required class="{{ $inputClasses }}" @error('body') aria-invalid="true" aria-describedby="comment-body-error" @enderror></textarea>
            @error('body') <p id="comment-body-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
            @error('replyTo') <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Honeypot for spam bots: hidden from sighted users and screen readers. --}}
        <div aria-hidden="true" class="sr-only">
            <label for="comment-website">{{ __('Leave this field empty') }}</label>
            <input id="comment-website" type="text" wire:model="website" tabindex="-1" autocomplete="off" />
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="rounded-lg bg-brand px-4 py-2 font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:opacity-60"
        >
            {{ __('Post comment') }}
        </button>
    </form>
</section>
