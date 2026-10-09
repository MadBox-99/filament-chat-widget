<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\Pages;

use Filament\Resources\Pages\ListRecords;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;

class ListChatConversations extends ListRecords
{
    protected static string $resource = ChatConversationResource::class;
}
