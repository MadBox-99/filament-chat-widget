<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages;

use Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatWidget extends CreateRecord
{
    protected static string $resource = ChatWidgetResource::class;
}
