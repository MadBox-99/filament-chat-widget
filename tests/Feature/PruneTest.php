<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatMessage;

it('keeps every conversation when no retention is configured', function (): void {
    ChatConversation::factory()->create(['last_message_at' => now()->subYears(5)]);

    $this->artisan('model:prune', ['--model' => [ChatConversation::class]])->assertSuccessful();

    expect(ChatConversation::withTrashed()->count())->toBe(1);
});

it('permanently deletes inactive conversations and their messages', function (): void {
    config()->set('filament-chat-widget.privacy.retention_days', 30);

    $old = ChatConversation::factory()->create(['last_message_at' => now()->subDays(31)]);
    $trashed = ChatConversation::factory()->create(['last_message_at' => now()->subDays(40)]);
    $trashed->delete();
    $recent = ChatConversation::factory()->create(['last_message_at' => now()->subDays(29)]);
    ChatMessage::factory()->count(2)->create(['chat_conversation_id' => $old->id]);
    ChatMessage::factory()->create(['chat_conversation_id' => $recent->id]);

    $this->artisan('model:prune', ['--model' => [ChatConversation::class]])->assertSuccessful();

    expect(ChatConversation::withTrashed()->pluck('id')->all())->toBe([$recent->id])
        ->and(ChatMessage::query()->count())->toBe(1);
});

it('schedules pruning only when retention is configured', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains((string) $event->command, 'model:prune'));

    expect($events)->toBeEmpty();
});
