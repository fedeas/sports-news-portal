@props(['article'])

{{-- The main story: the photograph across the column, the headline under it, the writer at its foot. --}}
<article class="group">
    @if ($article->featuredImage)
        {{-- The image repeats the title link, so keep it out of the tab order and away from screen readers. --}}
        <a
            href="{{ $article->url() }}"
            tabindex="-1"
            aria-hidden="true"
            class="block aspect-[16/9] overflow-hidden bg-zinc-200 dark:bg-zinc-800"
        >
            <x-media-image
                :media="$article->featuredImage"
                :lazy="false"
                fetchpriority="high"
                sizes="(min-width: 1024px) 45vw, 100vw"
                class="size-full object-cover"
            />
        </a>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1">
        @if ($article->is_breaking)
            <span class="figures bg-ink px-2 py-0.5 text-xs font-bold text-white dark:bg-white dark:text-ink">{{ __('Breaking news') }}</span>
        @endif

        <a href="{{ $article->category->url() }}" class="font-ui text-xs font-semibold hover:underline">
            {{ $article->category->name }}
        </a>
    </div>

    <h2 class="mt-1.5 text-2xl leading-[1.1] font-semibold text-balance lg:text-[1.875rem]">
        <a href="{{ $article->url() }}" class="hover:underline focus-visible:underline focus-visible:outline-none">
            {{ $article->title }}
        </a>
    </h2>

    @if ($article->excerpt)
        <p class="mt-2 text-base leading-snug text-zinc-600 dark:text-zinc-400">
            {{ $article->excerpt }}
        </p>
    @endif

    <x-byline :article="$article" class="mt-3 text-xs" />
</article>
