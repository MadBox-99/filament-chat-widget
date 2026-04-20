<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Models;

use Cegem360\FilamentChatWidget\Database\Factories\ChatConversationFactory;
use Cegem360\FilamentChatWidget\Enums\ChatConversationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Override;

class ChatConversation extends Model
{
    /** @use HasFactory<ChatConversationFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'chat_conversations';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected static function newFactory(): ChatConversationFactory
    {
        return ChatConversationFactory::new();
    }

    /**
     * Generic tenant relationship resolved from config.
     * Used by Filament for automatic tenant scoping.
     *
     * @return BelongsTo<Model, $this>
     */
    public function tenant(): BelongsTo
    {
        /** @var class-string<Model> $tenantModel */
        $tenantModel = (string) config('filament-chat-widget.tenant_model', Model::class);
        $foreignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        return $this->belongsTo($tenantModel, $foreignKey);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function assignedTo(): BelongsTo
    {
        /** @var class-string<Model> $agentModel */
        $agentModel = (string) config('filament-chat-widget.agent_model', \App\Models\User::class);

        return $this->belongsTo($agentModel, 'assigned_to');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    #[Override]
    protected static function booted(): void
    {
        self::creating(function (self $conversation): void {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ChatConversationStatus::class,
            'unread_count' => 'integer',
            'last_message_at' => 'datetime',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
