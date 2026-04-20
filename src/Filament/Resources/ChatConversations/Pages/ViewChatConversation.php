<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\Pages;

use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Override;

class ViewChatConversation extends ViewRecord
{
    protected static string $resource = ChatConversationResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
