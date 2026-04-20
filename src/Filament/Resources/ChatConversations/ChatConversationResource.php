<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations;

use BackedEnum;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Pages\EditChatConversation;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Pages\ListChatConversations;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Pages\ViewChatConversation;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\RelationManagers\ChatMessagesRelationManager;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Schemas\ChatConversationForm;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Tables\ChatConversationsTable;
use Cegem360\FilamentChatWidget\Models\ChatConversation;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class ChatConversationResource extends Resource
{
    protected static ?string $model = ChatConversation::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        $group = config('filament-chat-widget.filament.navigation_group');

        if (is_string($group) || $group === null) {
            return $group;
        }

        return null;
    }

    public static function isScopedToTenant(): bool
    {
        return (bool) config('filament-chat-widget.filament.scoped_to_tenant', true);
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('filament-chat-widget::chat.conversation.singular');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('filament-chat-widget::chat.conversation.plural');
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ChatConversationForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ChatConversationsTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            ChatMessagesRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListChatConversations::route('/'),
            'view' => ViewChatConversation::route('/{record}'),
            'edit' => EditChatConversation::route('/{record}/edit'),
        ];
    }
}
