<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;
use Madbox99\FilamentChatWidget\Enums\ChatSenderType;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatMessage;

final class ChatConversationFeed extends Component
{
    public int $conversationId;

    public string $newMessage = '';

    public int $lastMessageId = 0;

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $conversation = $this->conversation;

        if ($conversation instanceof ChatConversation && $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $this->lastMessageId = (int) $this->messages->max('id');
    }

    #[Computed]
    public function messages(): Collection
    {
        /** @var Collection<int, ChatMessage> $collection */
        $collection = ChatMessage::query()
            ->where('chat_conversation_id', $this->conversationId)
            ->orderBy('id')
            ->get();

        return $collection;
    }

    #[Computed]
    public function conversation(): ?ChatConversation
    {
        return ChatConversation::query()->find($this->conversationId);
    }

    public function poll(): void
    {
        $latestId = (int) ChatMessage::query()
            ->where('chat_conversation_id', $this->conversationId)
            ->max('id');

        if ($latestId <= $this->lastMessageId) {
            return;
        }

        $this->lastMessageId = $latestId;
        unset($this->messages);

        $conversation = $this->conversation;
        if ($conversation instanceof ChatConversation && $conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $this->dispatch('chat-feed-scroll-bottom');
    }

    public function sendMessage(): void
    {
        $text = trim($this->newMessage);

        if ($text === '') {
            return;
        }

        $conversation = $this->conversation;

        if (! $conversation instanceof ChatConversation) {
            return;
        }

        ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'sender_type' => ChatSenderType::Agent,
            'sender_id' => Auth::id(),
            'message' => $text,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0,
            'status' => $conversation->status === ChatConversationStatus::Closed
                ? ChatConversationStatus::Open
                : $conversation->status,
        ]);

        $this->newMessage = '';
        unset($this->messages);
        $this->lastMessageId = (int) $this->messages->max('id');

        $this->dispatch('chat-feed-scroll-bottom');
    }

    public function render(): View
    {
        return view('filament-chat-widget::livewire.chat-conversation-feed');
    }
}
