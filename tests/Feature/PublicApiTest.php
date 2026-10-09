<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;
use Madbox99\FilamentChatWidget\Enums\ChatSenderType;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatMessage;
use Madbox99\FilamentChatWidget\Models\ChatWidget;
use Madbox99\FilamentChatWidget\Tests\Fixtures\Team;

beforeEach(function (): void {
    $this->team = Team::query()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->widget = ChatWidget::factory()->create([
        'team_id' => $this->team->id,
        'title' => 'Hello',
        'auto_reply_message' => null,
    ]);
});

describe('widget config', function (): void {
    it('returns the public configuration', function (): void {
        $this->getJson('/chat/widget/acme')
            ->assertOk()
            ->assertJsonPath('title', 'Hello')
            ->assertJsonPath('is_online', true)
            ->assertJsonStructure(['labels' => ['placeholder', 'send', 'send_failed', 'close', 'open_chat', 'offline']]);
    });

    it('returns 404 for an unknown slug', function (): void {
        $this->getJson('/chat/widget/nope')->assertNotFound();
    });

    it('returns 404 for an inactive widget', function (): void {
        $this->widget->update(['is_active' => false]);

        $this->getJson('/chat/widget/acme')->assertNotFound();
    });

    it('localizes the widget labels from the locale query parameter', function (): void {
        $this->getJson('/chat/widget/acme?locale=hu')
            ->assertOk()
            ->assertJsonPath('labels.send', 'Küldés');

        $this->getJson('/chat/widget/acme?locale=en')
            ->assertOk()
            ->assertJsonPath('labels.send', 'Send');
    });

    it('ignores unsupported locales', function (): void {
        app()->setLocale('en');

        $this->getJson('/chat/widget/acme?locale=../../etc')
            ->assertOk()
            ->assertJsonPath('labels.send', 'Send');
    });

    it('reports offline status outside opening hours', function (): void {
        Carbon::setTestNow(Carbon::parse('2026-10-05 20:00', 'UTC')); // Monday

        $this->widget->update([
            'timezone' => 'UTC',
            'opening_hours' => [['day' => 'mon', 'from' => '09:00', 'to' => '17:00']],
        ]);

        $this->getJson('/chat/widget/acme')
            ->assertOk()
            ->assertJsonPath('is_online', false)
            ->assertJsonPath('offline_message', $this->widget->offline_message);
    });
});

describe('starting a conversation', function (): void {
    it('creates an anonymous conversation without storing the IP', function (): void {
        $response = $this->postJson('/chat/conversations', ['slug' => 'acme'])->assertCreated();

        $conversation = ChatConversation::query()->where('uuid', $response->json('uuid'))->sole();

        expect($conversation->team_id)->toBe($this->team->id)
            ->and($conversation->visitor_ip)->toBeNull()
            ->and($conversation->status)->toBe(ChatConversationStatus::Open);
    });

    it('stores the IP only when enabled in config', function (): void {
        config()->set('filament-chat-widget.privacy.store_visitor_ip', true);

        $uuid = $this->postJson('/chat/conversations', ['slug' => 'acme'])->json('uuid');

        expect(ChatConversation::query()->where('uuid', $uuid)->value('visitor_ip'))->toBe('127.0.0.1');
    });

    it('posts the auto reply as a system message', function (): void {
        $this->widget->update(['auto_reply_message' => 'We will reply soon']);

        $this->postJson('/chat/conversations', ['slug' => 'acme'])
            ->assertCreated()
            ->assertJsonPath('messages.0.sender_type', 'system')
            ->assertJsonPath('messages.0.message', 'We will reply soon');
    });
});

describe('messages', function (): void {
    beforeEach(function (): void {
        $this->conversation = ChatConversation::factory()->create([
            'team_id' => $this->team->id,
            'unread_count' => 0,
        ]);
    });

    it('stores a visitor message and increments the unread counter', function (): void {
        $this->postJson("/chat/conversations/{$this->conversation->uuid}/messages", ['message' => 'Hi'])
            ->assertCreated()
            ->assertJsonPath('message.sender_type', 'visitor')
            ->assertJsonPath('message.message', 'Hi');

        ChatConversation::query()->whereKey($this->conversation->id)->increment('unread_count', 5);

        $this->postJson("/chat/conversations/{$this->conversation->uuid}/messages", ['message' => 'Again'])
            ->assertCreated();

        expect($this->conversation->fresh()->unread_count)->toBe(7);
    });

    it('fires saved events for app observers when a visitor writes', function (): void {
        $saved = 0;
        ChatConversation::saved(function () use (&$saved): void {
            $saved++;
        });

        $this->postJson("/chat/conversations/{$this->conversation->uuid}/messages", ['message' => 'Hi'])->assertCreated();

        expect($saved)->toBe(1)
            ->and($this->conversation->fresh()->unread_count)->toBe(1);
    });

    it('reopens a closed conversation when the visitor writes again', function (): void {
        $this->conversation->update(['status' => ChatConversationStatus::Closed, 'closed_at' => now()]);

        $this->postJson("/chat/conversations/{$this->conversation->uuid}/messages", ['message' => 'Back'])
            ->assertCreated();

        expect($this->conversation->fresh())
            ->status->toBe(ChatConversationStatus::Open)
            ->closed_at->toBeNull();
    });

    it('rejects empty and oversized messages', function (): void {
        $url = "/chat/conversations/{$this->conversation->uuid}/messages";

        $this->postJson($url, ['message' => ''])->assertUnprocessable();
        $this->postJson($url, ['message' => str_repeat('a', 5001)])->assertUnprocessable();
    });

    it('returns only messages newer than the since parameter', function (): void {
        $first = ChatMessage::factory()->create(['chat_conversation_id' => $this->conversation->id, 'sender_type' => ChatSenderType::Visitor]);
        $second = ChatMessage::factory()->create(['chat_conversation_id' => $this->conversation->id, 'sender_type' => ChatSenderType::Agent]);

        $this->getJson("/chat/conversations/{$this->conversation->uuid}/messages?since={$first->id}")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $second->id);
    });

    it('returns 404 for an unknown conversation', function (): void {
        $this->getJson('/chat/conversations/00000000-0000-0000-0000-000000000000/messages')->assertNotFound();
        $this->postJson('/chat/conversations/00000000-0000-0000-0000-000000000000/messages', ['message' => 'x'])->assertNotFound();
    });
});

describe('single-tenant mode', function (): void {
    beforeEach(function (): void {
        config()->set('filament-chat-widget.tenant_model', null);
        app()->forgetInstance(ChatWidgetTenantResolver::class);

        ChatWidget::query()->delete();
        ChatWidget::factory()->create(['team_id' => null, 'title' => 'Solo']);
    });

    it('serves the widget under the default slug', function (): void {
        $this->getJson('/chat/widget/default')
            ->assertOk()
            ->assertJsonPath('title', 'Solo');
    });

    it('uses a resolver bound in code instead of single-tenant mode', function (): void {
        app()->instance(ChatWidgetTenantResolver::class, new class implements ChatWidgetTenantResolver
        {
            public function resolveTenantKeyBySlug(string $slug): int|string|null
            {
                return $slug === 'custom' ? 1 : null;
            }

            public function resolveSlugByTenantKey(int|string $tenantKey): ?string
            {
                return 'custom';
            }
        });
        ChatWidget::factory()->create(['team_id' => 1, 'title' => 'Custom']);

        $this->getJson('/chat/widget/custom')->assertOk()->assertJsonPath('title', 'Custom');
        $this->getJson('/chat/widget/default')->assertNotFound();
    });

    it('starts conversations without a tenant', function (): void {
        $uuid = $this->postJson('/chat/conversations', ['slug' => 'default'])->assertCreated()->json('uuid');

        expect(ChatConversation::query()->where('uuid', $uuid)->value('team_id'))->toBeNull();
    });
});
