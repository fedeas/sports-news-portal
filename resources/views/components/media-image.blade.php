@props([
    'media',
    'sizes' => '100vw',
    'lazy' => true,
    'alt' => null,
])

@php
    $srcset = $media->srcset();
@endphp

<img
    src="{{ $media->url() }}"
    @if ($srcset !== '')
        srcset="{{ $srcset }}"
        sizes="{{ $sizes }}"
    @endif
    alt="{{ $alt ?? $media->alt_text ?? '' }}"
    @if ($media->width && $media->height)
        width="{{ $media->width }}"
        height="{{ $media->height }}"
    @endif
    loading="{{ $lazy ? 'lazy' : 'eager' }}"
    decoding="async"
    {{ $attributes }}
/>
