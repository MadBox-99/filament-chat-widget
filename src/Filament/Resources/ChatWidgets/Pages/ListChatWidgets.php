<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages;

use Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListChatWidgets extends ListRecords
{
    protected static string $resource = ChatWidgetResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
