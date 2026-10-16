<?php

use Carbon\CarbonImmutable;

it('shows a UTC time in Cyprus time with Greek month names', function () {
    app()->setLocale('el');

    $this->blade('<x-local-datetime :value="$value" />', ['value' => CarbonImmutable::parse('2026-09-15 15:30:00', 'UTC')])
        ->assertSee('15 Σεπτεμβρίου 2026, 18:30')
        ->assertSee('datetime="2026-09-15T15:30:00+00:00"', false);
});

it('renders nothing without a value', function () {
    expect(trim((string) $this->blade('<x-local-datetime :value="null" />')))->toBe('');
});
