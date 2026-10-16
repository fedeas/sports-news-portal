{{-- The mark: a football. Panels and seams are cut out of the ball, so the background shows through them. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" {{ $attributes }}>
    <clipPath id="app-logo-ball-clip"><circle cx="12" cy="12" r="9.5" /></clipPath>
    <mask id="app-logo-ball">
        <circle cx="12" cy="12" r="10.4" fill="#fff" />
        <g clip-path="url(#app-logo-ball-clip)">
            <path d="M12 8.7 15.14 10.98 13.94 14.67 10.06 14.67 8.86 10.98ZM12 4.7 9.05 2.56 10.18-.91h3.64l1.13 3.47ZM18.94 9.75l1.13-3.47h3.64l1.13 3.47-2.95 2.14ZM16.29 17.9h3.64l1.13 3.47-2.95 2.14-2.95-2.14ZM7.71 17.9l1.13 3.47-2.95 2.14-2.95-2.14 1.13-3.47ZM5.06 9.75 2.11 11.89-.84 9.75l1.13-3.47h3.64Z" fill="#000" />
            <path d="M12 8.7v-4M15.14 10.98l3.8-1.23M13.94 14.67l2.35 3.23M10.06 14.67 7.71 17.9M8.86 10.98 5.06 9.75" stroke="#000" stroke-width="1.2" stroke-linecap="round" />
        </g>
    </mask>
    <rect width="24" height="24" fill="currentColor" mask="url(#app-logo-ball)" />
</svg>
