<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Madbox99\FilamentChatWidget\Models\ChatWidget;

function widgetWithHours(array $hours, ?string $timezone = 'Europe/Budapest'): ChatWidget
{
    return new ChatWidget(['opening_hours' => $hours, 'timezone' => $timezone]);
}

it('is always online without opening hours', function (?array $hours): void {
    expect((new ChatWidget(['opening_hours' => $hours]))->isOnlineAt(now()))->toBeTrue();
})->with([[null], [[]]]);

it('is online inside and offline outside the configured range', function (string $utc, bool $online): void {
    // 2026-10-05 is a Monday; Budapest is UTC+2 in October (CEST).
    $widget = widgetWithHours([['day' => 'mon', 'from' => '09:00', 'to' => '17:00']]);

    expect($widget->isOnlineAt(Carbon::parse($utc, 'UTC')))->toBe($online);
})->with([
    'before opening' => ['2026-10-05 06:59', false],
    'at opening' => ['2026-10-05 07:00', true],
    'midday' => ['2026-10-05 12:00', true],
    'at closing' => ['2026-10-05 15:00', false],
    'other day' => ['2026-10-06 12:00', false],
]);

it('supports several ranges on the same day', function (): void {
    $widget = widgetWithHours([
        ['day' => 'sat', 'from' => '08:00', 'to' => '10:00'],
        ['day' => 'sat', 'from' => '14:00', 'to' => '16:00'],
    ], 'UTC');

    expect($widget->isOnlineAt(Carbon::parse('2026-10-10 09:00', 'UTC')))->toBeTrue()
        ->and($widget->isOnlineAt(Carbon::parse('2026-10-10 12:00', 'UTC')))->toBeFalse()
        ->and($widget->isOnlineAt(Carbon::parse('2026-10-10 15:00', 'UTC')))->toBeTrue();
});

it('falls back to the app timezone and skips malformed entries', function (): void {
    config()->set('app.timezone', 'UTC');

    $widget = widgetWithHours([
        ['day' => 'sun'],
        ['day' => 'sun', 'from' => '10:00', 'to' => '11:00'],
    ], null);

    expect($widget->isOnlineAt(Carbon::parse('2026-10-11 10:30', 'UTC')))->toBeTrue();
});

it('accepts times stored with seconds', function (): void {
    $widget = widgetWithHours([['day' => 'mon', 'from' => '09:00:00', 'to' => '17:00:00']], 'UTC');

    expect($widget->isOnlineAt(Carbon::parse('2026-10-05 09:00', 'UTC')))->toBeTrue()
        ->and($widget->isOnlineAt(Carbon::parse('2026-10-05 17:00', 'UTC')))->toBeFalse();
});

it('handles ranges past midnight', function (string $utc, bool $online): void {
    // 2026-10-09 is a Friday.
    $widget = widgetWithHours([['day' => 'fri', 'from' => '22:00', 'to' => '02:00']], 'UTC');

    expect($widget->isOnlineAt(Carbon::parse($utc, 'UTC')))->toBe($online);
})->with([
    'friday evening' => ['2026-10-09 23:30', true],
    'saturday early' => ['2026-10-10 01:59', true],
    'saturday after close' => ['2026-10-10 02:00', false],
    'friday early (belongs to thursday)' => ['2026-10-09 01:00', false],
]);

it('falls back to the app timezone when the stored timezone is invalid', function (): void {
    config()->set('app.timezone', 'UTC');
    $widget = widgetWithHours([['day' => 'mon', 'from' => '09:00', 'to' => '17:00']], 'Not/AZone');

    expect($widget->isOnlineAt(Carbon::parse('2026-10-05 10:00', 'UTC')))->toBeTrue();
});
