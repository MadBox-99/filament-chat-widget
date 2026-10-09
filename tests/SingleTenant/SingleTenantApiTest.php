<?php

declare(strict_types=1);

use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatWidget;

describe('single-tenant mode', function (): void {
    beforeEach(function (): void {
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
