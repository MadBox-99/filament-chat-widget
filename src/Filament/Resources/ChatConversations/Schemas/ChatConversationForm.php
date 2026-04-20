<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatConversations\Schemas;

use Cegem360\FilamentChatWidget\Enums\ChatConversationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ChatConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-chat-widget::chat.sections.conversation_details'))
                    ->schema([
                        TextInput::make('visitor_name')
                            ->label(__('filament-chat-widget::chat.fields.visitor_name'))
                            ->maxLength(255),
                        TextInput::make('visitor_email')
                            ->label(__('filament-chat-widget::chat.fields.visitor_email'))
                            ->email()
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('filament-chat-widget::chat.fields.status'))
                            ->options(ChatConversationStatus::class)
                            ->required(),
                        Select::make('assigned_to')
                            ->label(__('filament-chat-widget::chat.fields.assigned_to'))
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }
}
