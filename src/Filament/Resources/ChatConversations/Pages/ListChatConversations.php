<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Pages;

use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListChatConversations extends ListRecords
{
    protected static string $resource = ChatConversationResource::class;
}
