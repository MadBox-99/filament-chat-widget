<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;

class CreateChatWidget extends CreateRecord
{
    protected static string $resource = ChatWidgetResource::class;
}
