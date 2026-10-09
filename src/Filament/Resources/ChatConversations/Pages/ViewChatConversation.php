<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;
use Madbox99\FilamentChatWidget\Filament\Resources\ChatConversations\ChatConversationResource;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Override;

class ViewChatConversation extends ViewRecord
{
    protected static string $resource = ChatConversationResource::class;

    protected string $view = 'filament-chat-widget::filament.pages.view-chat-conversation';

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignToMe')
                ->label(__('filament-chat-widget::chat.actions.assign_to_me'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('gray')
                ->visible(fn (ChatConversation $record): bool => $record->assigned_to !== Auth::id())
                ->action(function (ChatConversation $record): void {
                    $record->update(['assigned_to' => Auth::id()]);

                    Notification::make()->success()->title(__('filament-chat-widget::chat.notifications.assigned'))->send();
                }),
            Action::make('closeConversation')
                ->label(__('filament-chat-widget::chat.actions.close_conversation'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (ChatConversation $record): bool => $record->status !== ChatConversationStatus::Closed)
                ->action(function (ChatConversation $record): void {
                    $record->update(['status' => ChatConversationStatus::Closed, 'closed_at' => now()]);

                    Notification::make()->success()->title(__('filament-chat-widget::chat.notifications.closed'))->send();
                }),
            Action::make('reopenConversation')
                ->label(__('filament-chat-widget::chat.actions.reopen_conversation'))
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('gray')
                ->visible(fn (ChatConversation $record): bool => $record->status === ChatConversationStatus::Closed)
                ->action(function (ChatConversation $record): void {
                    $record->update(['status' => ChatConversationStatus::Open, 'closed_at' => null]);

                    Notification::make()->success()->title(__('filament-chat-widget::chat.notifications.reopened'))->send();
                }),
            EditAction::make(),
        ];
    }
}
