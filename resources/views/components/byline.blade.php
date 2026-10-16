@props([
    'article',
    'withDate' => true,
    'dateFormat' => 'j F Y, H:i',
])

{{-- Who wrote it, at the foot of every story: the writer on the left, the time on the right. --}}
<p {{ $attributes->class('font-ui flex items-baseline justify-between gap-3 text-zinc-500 dark:text-zinc-400') }}>
    <span class="min-w-0 truncate font-semibold text-zinc-700 dark:text-zinc-300">
        @if ($article->author->is_active)
            <a href="{{ $article->author->url() }}" class="hover:text-ink hover:underline dark:hover:text-white">{{ $article->author->name }}</a>
        @else
            {{ $article->author->name }}
        @endif
    </span>

    @if ($withDate && $article->published_at)
        <x-local-datetime :value="$article->published_at" :format="$dateFormat" class="figures shrink-0" />
    @endif
</p>
