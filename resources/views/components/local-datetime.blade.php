@props([
    'value',
    'format' => 'j F Y, H:i',
])

@if ($value)
    <time datetime="{{ $value->toIso8601String() }}" {{ $attributes }}>
        {{ $value->timezone(config('app.display_timezone'))->locale(app()->getLocale())->translatedFormat($format) }}
    </time>
@endif
