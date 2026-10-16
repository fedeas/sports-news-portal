@props([
    'article',
    'headingLevel' => 3,
    'showThumbnail' => true,
    'showDate' => true,
    // 'md' for lists and the rail; 'lg' for the stories beside the lead and the news feed.
    'size' => 'md',
    'showExcerpt' => false,
])

@php
    $headingTag = 'h'.max(2, min(6, (int) $headingLevel));
    $isLarge = $size === 'lg';
@endphp

{{-- A story in a list, picture on the left and words on the right, the way news feeds run. --}}
<article {{ $attributes->class(['flex', 'gap-3' => ! $isLarge, 'gap-4' => $isLarge]) }}>
    @if ($showThumbnail)
        <a
            href="{{ $article->url() }}"
            tabindex="-1"
            aria-hidden="true"
            @class([
                'block aspect-[4/3] shrink-0 overflow-hidden bg-zinc-200 dark:bg-zinc-800',
                'w-28 sm:w-36' => ! $isLarge,
                'w-32 sm:w-48' => $isLarge,
            ])
        >
            @if ($article->featuredImage)
                <x-media-image
                    :media="$article->featuredImage"
                    :sizes="$isLarge ? '(min-width: 640px) 192px, 128px' : '(min-width: 640px) 144px, 112px'"
                    class="size-full object-cover"
                />
            @endif
        </a>
    @endif

    <div class="min-w-0 flex-1">
        <a href="{{ $article->category->url() }}" class="font-ui text-xs font-semibold text-zinc-600 hover:text-ink hover:underline dark:text-zinc-400 dark:hover:text-white">
            {{ $article->category->name }}
        </a>

        <{{ $headingTag }} @class([
            'mt-0.5 leading-tight font-semibold text-balance',
            'text-base' => ! $isLarge,
            'text-base sm:text-lg' => $isLarge,
        ])>
            <a href="{{ $article->url() }}" class="hover:underline focus-visible:underline focus-visible:outline-none">
                {{ $article->title }}
            </a>
        </{{ $headingTag }}>

        @if ($showExcerpt && $article->excerpt)
            <p class="mt-1 line-clamp-2 text-sm leading-snug text-zinc-600 dark:text-zinc-400">{{ $article->excerpt }}</p>
        @endif

        <x-byline :article="$article" :with-date="$showDate" date-format="j M, H:i" class="mt-1.5 text-xs" />
    </div>
</article>
