<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Pages;

use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
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
