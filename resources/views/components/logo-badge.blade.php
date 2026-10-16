@props([
    'media' => null,
    'name',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-8 text-xs',
        'lg' => 'size-24 text-2xl',
        default => 'size-12 text-sm',
    };

    $initials = Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $word) => mb_substr($word, 0, 1))
        ->join('');
@endphp

{{-- Logos sit next to the name, so they are decorative (empty alt / aria-hidden). --}}
@if ($media)
    <x-media-image :media="$media" alt="" sizes="96px" class="{{ $sizeClasses }} shrink-0 object-contain" />
@else
    <span
        aria-hidden="true"
        class="{{ $sizeClasses }} inline-flex shrink-0 items-center justify-center rounded-full bg-zinc-100 font-bold text-zinc-600 uppercase dark:bg-zinc-800 dark:text-zinc-300"
    >
        {{ $initials }}
    </span>
@endif
