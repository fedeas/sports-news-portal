@props([
    'article',
    'lazy' => true,
    'headingLevel' => 2,
])

@php
    $headingTag = 'h'.max(2, min(6, (int) $headingLevel));
@endphp

<article {{ $attributes->class('group flex flex-col') }}>
    {{-- The image repeats the title link, so keep it out of the tab order and away from screen readers. --}}
    <a href="{{ $article->url() }}" tabindex="-1" aria-hidden="true" class="block aspect-[4/3] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
        @if ($article->featuredImage)
            <x-media-image
                :media="$article->featuredImage"
                :lazy="$lazy"
                sizes="(min-width: 1280px) 33vw, (min-width: 640px) 50vw, 100vw"
                class="size-full object-cover"
            />
        @endif
    </a>

    {{-- A hairline in the section colour ties the card to its section without boxing it in. --}}
    <div class="mt-3 flex flex-1 flex-col border-t-2 border-ink pt-2.5 dark:border-white">
        <a href="{{ $article->category->url() }}" class="font-ui text-xs font-semibold text-zinc-600 hover:text-ink hover:underline dark:text-zinc-400 dark:hover:text-white">
            {{ $article->category->name }}
        </a>

        <{{ $headingTag }} class="mt-1 mb-auto text-xl leading-tight font-semibold text-balance">
            <a href="{{ $article->url() }}" class="hover:underline focus-visible:underline focus-visible:outline-none">
                {{ $article->title }}
            </a>
        </{{ $headingTag }}>

        <x-byline :article="$article" class="mt-2 text-xs" />
    </div>
</article>
