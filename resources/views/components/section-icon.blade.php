@props([
    // A SectionIcon, its string value, or null for nothing.
    'icon',
])

@php
    $icon = $icon instanceof \App\Enums\SectionIcon ? $icon : \App\Enums\SectionIcon::tryFrom((string) $icon);
@endphp

{{-- Line pictograms drawn for the site in the current text colour, so they stay black and white. --}}
@if ($icon)
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        data-section-icon="{{ $icon->value }}"
        {{ $attributes->class('shrink-0') }}
    >
        @switch($icon)
            @case(\App\Enums\SectionIcon::Football)
                <circle cx="12" cy="12" r="9" />
                <path d="m12 8.7 3.14 2.28-1.2 3.69h-3.88l-1.2-3.69z" />
                <path d="M12 8.7V3m3.14 7.98 5.42-1.76m-6.62 5.45 3.35 4.61m-7.23-4.61-3.35 4.61m2.15-8.3L3.44 9.22" />
                @break
            @case(\App\Enums\SectionIcon::Basketball)
                <circle cx="12" cy="12" r="9" />
                <path d="M12 3v18M3 12h18" />
                <path d="M5.6 5.6c3.4 3.4 3.4 9.4 0 12.8M18.4 5.6c-3.4 3.4-3.4 9.4 0 12.8" />
                @break
            @case(\App\Enums\SectionIcon::Volleyball)
                <circle cx="12" cy="12" r="9" />
                {{-- Three seams turning out from the centre, a third of a turn apart. --}}
                <path d="M12 12C14.5 13.6 17.6 14.1 20.6 13.3M12 12C9.36 13.37 7.38 15.8 6.57 18.8M12 12C12.14 9.03 11.02 6.1 8.83 3.9" />
                <path d="M8.4 13.9C10.5 16.9 13.6 19 17.1 19.5M12.15 7.93C8.51 8.25 5.14 9.89 2.95 12.67M15.45 14.17C16.99 10.85 17.26 7.11 15.95 3.83" />
                @break
            @case(\App\Enums\SectionIcon::Tennis)
                <ellipse cx="10" cy="9" rx="5.5" ry="6.5" transform="rotate(-45 10 9)" />
                <path d="m14.6 13.6 5.9 5.9" />
                <path d="M6.6 9.6 10.4 5.8M9.6 12.6l3.8-3.8" />
                <circle cx="18.5" cy="5.5" r="1.75" />
                @break
            @case(\App\Enums\SectionIcon::Athletics)
                <circle cx="12" cy="13.5" r="7.5" />
                <path d="M12 13.5V9.5M10 2.5h4M12 2.5V6M18.3 7.2l1.4-1.4" />
                @break
            @case(\App\Enums\SectionIcon::CombatSports)
                <path d="M6 11a5 5 0 0 1 5-5h3a5 5 0 0 1 5 5v2.5a4 4 0 0 1-4 4H9a3 3 0 0 1-3-3z" />
                <path d="M6 12.5c2.3.2 4-.5 5-2.2M8.5 17.5V21h8v-3.5" />
                @break
            @case(\App\Enums\SectionIcon::OtherSports)
                <path d="M8 3h8M8 3l2.6 6.4M16 3l-2.6 6.4" />
                <circle cx="12" cy="15" r="6" />
                <path d="m12 12 .9 1.9 2.1.3-1.5 1.4.4 2.1-1.9-1-1.9 1 .4-2.1-1.5-1.4 2.1-.3z" />
                @break
            @case(\App\Enums\SectionIcon::Transfers)
                <path d="M4 8h15m-4-4 4 4-4 4" />
                <path d="M20 16H5m4-4-4 4 4 4" />
                @break
            @case(\App\Enums\SectionIcon::NationalTeams)
                <path d="M5 21V3.5" />
                <path d="M5 4h12l-2.5 4.5L17 13H5" />
                @break
            @case(\App\Enums\SectionIcon::Opinion)
                <path d="M4 5h16v11H10l-5 4v-4H4z" />
                <path d="M8 9h8M8 12h5" />
                @break
            @case(\App\Enums\SectionIcon::News)
                <path d="M4 5h13v14H6a2 2 0 0 1-2-2z" />
                <path d="M17 9h3v8a2 2 0 0 1-2 2M7 8.5h7M7 11.5h7M7 14.5h4" />
                @break
        @endswitch
    </svg>
@endif
