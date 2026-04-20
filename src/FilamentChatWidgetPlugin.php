<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget;

use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class FilamentChatWidgetPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'filament-chat-widget';
    }

    public function register(Panel $panel): void
    {
        $resources = [];

        if ((bool) config('filament-chat-widget.filament.register_chat_widgets_resource', true)) {
            $resources[] = ChatWidgetResource::class;
        }

        if ((bool) config('filament-chat-widget.filament.register_chat_conversations_resource', true)) {
            $resources[] = ChatConversationResource::class;
        }

        if ($resources !== []) {
            $panel->resources($resources);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
