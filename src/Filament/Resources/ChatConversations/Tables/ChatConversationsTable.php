<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;

final class ChatConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor_name')
                    ->label(__('filament-chat-widget::chat.fields.visitor_name'))
                    ->placeholder(__('filament-chat-widget::chat.feed.anonymous'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('visitor_email')
                    ->label(__('filament-chat-widget::chat.fields.visitor_email'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('filament-chat-widget::chat.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignedTo.name')
                    ->label(__('filament-chat-widget::chat.fields.assigned_to'))
                    ->sortable(),
                TextColumn::make('unread_count')
                    ->label(__('filament-chat-widget::chat.fields.unread_count'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('last_message_at')
                    ->label(__('filament-chat-widget::chat.fields.last_message_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('filament-chat-widget::chat.fields.started_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->poll('10s')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('filament-chat-widget::chat.fields.status'))
                    ->options(ChatConversationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
