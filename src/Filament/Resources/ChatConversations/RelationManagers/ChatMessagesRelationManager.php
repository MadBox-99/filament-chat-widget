<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\RelationManagers;

use Cegem360\FilamentChatWidget\Enums\ChatSenderType;
use Cegem360\FilamentChatWidget\Models\ChatConversation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Override;

class ChatMessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('sender_type')
                    ->default(ChatSenderType::Agent->value),
                Hidden::make('sender_id')
                    ->default(fn (): ?int => Auth::id()),
                Textarea::make('message')
                    ->label(__('filament-chat-widget::chat.fields.message'))
                    ->required()
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('sender_type')
                    ->label(__('filament-chat-widget::chat.fields.sender'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('message')
                    ->limit(100)
                    ->searchable(),
                TextColumn::make('read_at')
                    ->label(__('filament-chat-widget::chat.fields.read_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament-chat-widget::chat.fields.sent_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label(__('filament-chat-widget::chat.actions.reply'))
                    ->after(function (Model $record): void {
                        $conversation = $record->conversation;

                        if ($conversation instanceof ChatConversation) {
                            $conversation->update([
                                'last_message_at' => now(),
                                'unread_count' => 0,
                            ]);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
