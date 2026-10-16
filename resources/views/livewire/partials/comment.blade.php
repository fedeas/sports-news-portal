{{-- A single comment. The body is plain text: escaped, with line breaks kept by CSS. --}}
<article class="space-y-1">
    <header class="flex flex-wrap items-baseline gap-x-2 text-sm">
        <span class="font-semibold">{{ $comment->authorName() }}</span>
        @if ($comment->user_id)
            <span class="rounded bg-zinc-100 px-1.5 text-xs font-medium dark:bg-zinc-800">{{ __('Staff') }}</span>
        @endif
        <x-local-datetime :value="$comment->created_at" class="text-zinc-500 dark:text-zinc-400" />
    </header>

    <p class="whitespace-pre-line break-words text-zinc-800 dark:text-zinc-200">{{ $comment->body }}</p>
</article>
