<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages\CreateChatWidget;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages\EditChatWidget;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages\ListChatWidgets;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Schemas\ChatWidgetForm;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Tables\ChatWidgetsTable;
use Madbox99\FilamentChatWidget\Models\ChatWidget;
use Override;

class ChatWidgetResource extends Resource
{
    protected static ?string $model = ChatWidget::class;

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

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
        return __('filament-chat-widget::chat.widget.singular');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('filament-chat-widget::chat.widget.plural');
    }

    /**
     * The public API serves exactly one widget per tenant (and the tenant
     * foreign key is unique), so creation is offered only while none exists.
     */
    #[Override]
    public static function getCreateAuthorizationResponse(): Response
    {
        $response = parent::getCreateAuthorizationResponse();

        if ($response->denied() || static::getEloquentQuery()->doesntExist()) {
            return $response;
        }

        return Response::deny(__('filament-chat-widget::chat.validation.widget_exists'));
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ChatWidgetForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ChatWidgetsTable::configure($table);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListChatWidgets::route('/'),
            'create' => CreateChatWidget::route('/create'),
            'edit' => EditChatWidget::route('/{record}/edit'),
        ];
    }
}
