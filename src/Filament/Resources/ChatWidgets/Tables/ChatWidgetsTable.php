<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ChatWidgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('filament-chat-widget::chat.fields.title'))
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label(__('filament-chat-widget::chat.fields.color')),
                TextColumn::make('position')
                    ->label(__('filament-chat-widget::chat.fields.position'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('filament-chat-widget::chat.fields.active'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
