<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Models;

use Madbox99\FilamentChatWidget\Database\Factories\ChatMessageFactory;
use Madbox99\FilamentChatWidget\Enums\ChatSenderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'read_at',
    ];

    protected static function newFactory(): ChatMessageFactory
    {
        return ChatMessageFactory::new();
    }

    /**
     * @return BelongsTo<ChatConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function sender(): BelongsTo
    {
        /** @var class-string<Model> $agentModel */
        $agentModel = (string) config('filament-chat-widget.agent_model', \App\Models\User::class);

        return $this->belongsTo($agentModel, 'sender_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_type' => ChatSenderType::class,
            'read_at' => 'datetime',
        ];
    }
}
