<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;
use Madbox99\FilamentChatWidget\Enums\ChatSenderType;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\Pages\ViewChatConversation;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages\EditChatWidget;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages\ListChatWidgets;
use Madbox99\FilamentChatWidget\Livewire\ChatConversationFeed;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatMessage;
use Madbox99\FilamentChatWidget\Models\ChatWidget;
use Madbox99\FilamentChatWidget\Tests\Fixtures\Team;
use Madbox99\FilamentChatWidget\Tests\Fixtures\User;

/**
 * Filament assigns new records to the current tenant on creation, so records
 * belonging to another tenant must be created while that tenant is active.
 */
function inTenant(Team $tenant, callable $callback): mixed
{
    $previous = Filament::getTenant();
    Filament::setTenant($tenant);

    try {
        return $callback();
    } finally {
        Filament::setTenant($previous);
    }
}

beforeEach(function (): void {
    $this->team = Team::query()->create(['name' => 'Acme', 'slug' => 'acme']);
    $this->otherTeam = Team::query()->create(['name' => 'Other', 'slug' => 'other']);
    $this->user = User::query()->create(['name' => 'Agent', 'email' => 'agent@example.com']);
    $this->user->teams()->attach($this->team);

    $this->actingAs($this->user);
    Filament::setCurrentPanel('admin');
    Filament::setTenant($this->team);
    Filament::bootCurrentPanel();
});

describe('conversation feed', function (): void {
    it('lets an agent reply, assigns the conversation and resets the unread counter', function (): void {
        $conversation = ChatConversation::factory()->create(['team_id' => $this->team->id, 'unread_count' => 3]);

        Livewire::test(ChatConversationFeed::class, ['conversationId' => $conversation->id])
            ->set('newMessage', 'Hello there')
            ->call('sendMessage')
            ->assertSet('newMessage', '');

        $message = ChatMessage::query()->latest('id')->first();

        expect($message->sender_type)->toBe(ChatSenderType::Agent)
            ->and($message->sender_id)->toBe($this->user->id)
            ->and($conversation->fresh())
            ->unread_count->toBe(0)
            ->assigned_to->toBe($this->user->id);
    });

    it('does not override an existing assignee when replying', function (): void {
        $other = User::query()->create(['name' => 'Boss', 'email' => 'boss@example.com']);
        $conversation = ChatConversation::factory()->create(['team_id' => $this->team->id, 'assigned_to' => $other->id]);

        Livewire::test(ChatConversationFeed::class, ['conversationId' => $conversation->id])
            ->set('newMessage', 'Hi')
            ->call('sendMessage');

        expect($conversation->fresh()->assigned_to)->toBe($other->id);
    });

    it('locks the conversation id against client-side tampering', function (): void {
        $mine = ChatConversation::factory()->create(['team_id' => $this->team->id]);
        $foreign = inTenant($this->otherTeam, fn () => ChatConversation::factory()->create());

        Livewire::test(ChatConversationFeed::class, ['conversationId' => $mine->id])
            ->set('conversationId', $foreign->id);
    })->throws(CannotUpdateLockedPropertyException::class);

    it('refuses to mount a conversation from another tenant', function (): void {
        $foreign = inTenant($this->otherTeam, fn () => ChatConversation::factory()->create());

        Livewire::test(ChatConversationFeed::class, ['conversationId' => $foreign->id])
            ->assertNotFound();
    });
});

describe('conversation view actions', function (): void {
    it('closes and reopens a conversation', function (): void {
        $conversation = ChatConversation::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(ViewChatConversation::class, ['record' => $conversation->getRouteKey()])
            ->assertActionHidden('reopenConversation')
            ->callAction('closeConversation');

        expect($conversation->fresh())
            ->status->toBe(ChatConversationStatus::Closed)
            ->closed_at->not->toBeNull();

        Livewire::test(ViewChatConversation::class, ['record' => $conversation->getRouteKey()])
            ->assertActionHidden('closeConversation')
            ->callAction('reopenConversation');

        expect($conversation->fresh())
            ->status->toBe(ChatConversationStatus::Open)
            ->closed_at->toBeNull();
    });

    it('assigns a conversation to the current user', function (): void {
        $conversation = ChatConversation::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(ViewChatConversation::class, ['record' => $conversation->getRouteKey()])
            ->callAction('assignToMe');

        expect($conversation->fresh()->assigned_to)->toBe($this->user->id);

        Livewire::test(ViewChatConversation::class, ['record' => $conversation->getRouteKey()])
            ->assertActionHidden('assignToMe');
    });
});

describe('navigation badge', function (): void {
    it('counts unread conversations of the current tenant only', function (): void {
        ChatConversation::factory()->count(2)->create(['team_id' => $this->team->id, 'unread_count' => 4]);
        ChatConversation::factory()->create(['team_id' => $this->team->id, 'unread_count' => 0]);
        inTenant($this->otherTeam, fn () => ChatConversation::factory()->create(['unread_count' => 9]));

        expect(ChatConversationResource::getNavigationBadge())->toBe('2');
    });

    it('hides the badge when nothing is unread', function (): void {
        expect(ChatConversationResource::getNavigationBadge())->toBeNull();
    });
});

describe('widget resource', function (): void {
    it('allows creating a widget only while the tenant has none', function (): void {
        expect(ChatWidgetResource::canCreate())->toBeTrue();

        inTenant($this->otherTeam, fn () => ChatWidget::factory()->create());
        expect(ChatWidgetResource::canCreate())->toBeTrue();

        ChatWidget::factory()->create(['team_id' => $this->team->id]);
        expect(ChatWidgetResource::canCreate())->toBeFalse();

        Livewire::test(ListChatWidgets::class)->assertActionHidden('create');
    });

    it('no longer registers the legacy messages relation manager', function (): void {
        expect(ChatConversationResource::getRelations())->toBe([]);
    });
});

describe('widget form', function (): void {
    it('saves structured opening hours', function (): void {
        $widget = ChatWidget::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(EditChatWidget::class, ['record' => $widget->getRouteKey()])
            ->fillForm([
                'timezone' => 'Europe/Budapest',
                'opening_hours' => [
                    ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($widget->fresh())
            ->timezone->toBe('Europe/Budapest')
            ->opening_hours->toBe([['day' => 'mon', 'from' => '09:00', 'to' => '17:00']]);
    });

    it('rejects a range that opens and closes at the same time', function (): void {
        $widget = ChatWidget::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(EditChatWidget::class, ['record' => $widget->getRouteKey()])
            ->fillForm(['opening_hours' => [['day' => 'mon', 'from' => '09:00', 'to' => '09:00']]])
            ->call('save')
            ->assertHasFormErrors();
    });

    it('builds the embed snippet with the tenant slug', function (): void {
        $widget = ChatWidget::factory()->create(['team_id' => $this->team->id]);

        Livewire::test(EditChatWidget::class, ['record' => $widget->getRouteKey()])
            ->mountAction('embedCode')
            ->assertSchemaStateSet(['script_snippet' => '<script src="http://localhost/chat/embed.js" data-team="acme" async></script>'], 'mountedActionSchema0');
    });
});
