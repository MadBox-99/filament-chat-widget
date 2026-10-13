<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatWidget;
use Madbox99\FilamentChatWidget\Tests\Fixtures\Team;

beforeEach(function (): void {
    $this->team = Team::query()->create(['name' => 'Acme', 'slug' => 'acme']);
    ChatWidget::factory()->create(['team_id' => $this->team->id, 'auto_reply_message' => null]);
});

function startFrom(string $ip): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/chat/conversations', ['slug' => 'acme']);
}

it('keeps polling from eating into the send quota', function (): void {
    $uuid = startFrom('10.0.0.1')->assertSuccessful()->json('uuid');

    // A minute of the open panel polling every 4 seconds is past the default send limit of 20.
    foreach (range(1, 25) as $ignored) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->getJson("/chat/conversations/{$uuid}/messages")->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->postJson("/chat/conversations/{$uuid}/messages", ['message' => 'Hello'])
        ->assertSuccessful();
});

it('caps the conversations one IP may start in a day', function (): void {
    config()->set('filament-chat-widget.routes.throttle.start', '100,1');
    config()->set('filament-chat-widget.routes.daily_limits.start', 3);

    foreach (range(1, 3) as $ignored) {
        startFrom('10.0.0.2')->assertSuccessful();
    }

    startFrom('10.0.0.2')->assertTooManyRequests();

    // Another visitor is not affected, and the cap resets the next day.
    startFrom('10.0.0.3')->assertSuccessful();

    Carbon::setTestNow(now()->addDay()->addMinute());
    startFrom('10.0.0.2')->assertSuccessful();

    expect(ChatConversation::query()->count())->toBe(5);
});

it('caps the messages one IP may send in a day', function (): void {
    config()->set('filament-chat-widget.routes.throttle.send', '100,1');
    config()->set('filament-chat-widget.routes.daily_limits.send', 2);
    $uuid = startFrom('10.0.0.4')->json('uuid');
    $send = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
        ->postJson("/chat/conversations/{$uuid}/messages", ['message' => 'Hi']);

    $send()->assertSuccessful();
    $send()->assertSuccessful();
    $send()->assertTooManyRequests();
});

it('applies the daily cap even when the app published an older config without it', function (): void {
    config()->set('filament-chat-widget.routes', array_diff_key(config('filament-chat-widget.routes'), ['daily_limits' => true]));
    config()->set('filament-chat-widget.routes.throttle.start', '100,1');

    foreach (range(1, 10) as $ignored) {
        startFrom('10.0.0.5')->assertSuccessful();
    }

    startFrom('10.0.0.5')->assertTooManyRequests();
});

it('lets a null daily limit disable the cap', function (): void {
    config()->set('filament-chat-widget.routes.throttle.start', '100,1');
    config()->set('filament-chat-widget.routes.daily_limits.start', null);

    foreach (range(1, 12) as $ignored) {
        startFrom('10.0.0.6')->assertSuccessful();
    }
});
