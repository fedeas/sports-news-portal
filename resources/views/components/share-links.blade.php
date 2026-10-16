@props(['url', 'title'])

{{-- Plain links, so sharing works without loading anything from the networks (and without their trackers). --}}
{{-- The URL goes through @js, never straight into the click handler, so it cannot break out of the string. --}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }} x-data="{ copied: false, url: @js($url) }">
    <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">{{ __('Share') }}</span>

    @foreach ([
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($url),
        'X' => 'https://x.com/intent/post?url='.urlencode($url).'&text='.urlencode($title),
        'WhatsApp' => 'https://wa.me/?text='.urlencode($title.' '.$url),
        'Viber' => 'viber://forward?text='.urlencode($title.' '.$url),
    ] as $network => $shareUrl)
        <a
            href="{{ $shareUrl }}"
            target="_blank"
            rel="noopener nofollow"
            class="inline-block rounded-full border border-zinc-200 px-3 py-1 text-sm font-medium hover:border-brand hover:text-brand-content dark:border-zinc-700"
        >
            <span class="sr-only">{{ __('Share on :network', ['network' => $network]) }}</span>
            <span aria-hidden="true">{{ $network }}</span>
        </a>
    @endforeach

    <button
        type="button"
        x-on:click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)"
        class="inline-block rounded-full border border-zinc-200 px-3 py-1 text-sm font-medium hover:border-brand hover:text-brand-content dark:border-zinc-700"
    >
        <span x-show="! copied">{{ __('Copy link') }}</span>
        <span x-show="copied" x-cloak>{{ __('Link copied') }}</span>
    </button>
</div>
