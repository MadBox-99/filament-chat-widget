<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatWidgets\Schemas;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ChatWidgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-chat-widget::chat.sections.widget_configuration'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('filament-chat-widget::chat.fields.title'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('welcome_message')
                            ->label(__('filament-chat-widget::chat.fields.welcome_message'))
                            ->maxLength(65535),
                        ColorPicker::make('color')
                            ->label(__('filament-chat-widget::chat.fields.color'))
                            ->default('#6366f1'),
                        Select::make('position')
                            ->label(__('filament-chat-widget::chat.fields.position'))
                            ->options([
                                'bottom-right' => __('filament-chat-widget::chat.positions.bottom_right'),
                                'bottom-left' => __('filament-chat-widget::chat.positions.bottom_left'),
                            ])
                            ->default('bottom-right')
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('filament-chat-widget::chat.fields.active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('filament-chat-widget::chat.sections.messaging'))
                    ->schema([
                        Textarea::make('offline_message')
                            ->label(__('filament-chat-widget::chat.fields.offline_message'))
                            ->maxLength(65535),
                        Textarea::make('auto_reply_message')
                            ->label(__('filament-chat-widget::chat.fields.auto_reply_message'))
                            ->maxLength(65535),
                    ])
                    ->columns(2),

                Section::make(__('filament-chat-widget::chat.sections.business_hours'))
                    ->schema([
                        KeyValue::make('business_hours')
                            ->label(__('filament-chat-widget::chat.fields.business_hours'))
                            ->keyLabel(__('filament-chat-widget::chat.fields.day'))
                            ->valueLabel(__('filament-chat-widget::chat.fields.hours'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-chat-widget::chat.sections.custom_css'))
                    ->description(__('filament-chat-widget::chat.fields.custom_css_help'))
                    ->collapsed()
                    ->schema([
                        CodeEditor::make('custom_css')
                            ->label(__('filament-chat-widget::chat.fields.custom_css'))
                            ->language(Language::Css)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
