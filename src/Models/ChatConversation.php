<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;
use Madbox99\FilamentChatWidget\Database\Factories\ChatConversationFactory;
use Madbox99\FilamentChatWidget\Enums\ChatConversationStatus;
use Madbox99\FilamentChatWidget\Support\ChatTenancy;
use Override;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string|null $visitor_name
 * @property string|null $visitor_email
 * @property string|null $visitor_ip
 * @property ChatConversationStatus $status
 * @property int|null $assigned_to
 * @property int $unread_count
 * @property Carbon|null $last_message_at
 * @property Carbon|null $started_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ChatConversation extends Model
{
    /** @use HasFactory<ChatConversationFactory> */
    use HasFactory;

    use Prunable;
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
        $tenantModel = ChatTenancy::model()
            ?? throw new LogicException('No chat widget tenant model: set `filament-chat-widget.tenant_model` or use a Filament panel with tenancy.');

        return $this->belongsTo($tenantModel, ChatTenancy::foreignKey());
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function assignedTo(): BelongsTo
    {
        /** @var class-string<Model> $agentModel */
        $agentModel = (string) config('filament-chat-widget.agent_model', 'App\Models\User');

        return $this->belongsTo($agentModel, 'assigned_to');
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Conversations without activity for `privacy.retention_days` are
     * permanently deleted (messages cascade). Disabled when the setting is null.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $days = config('filament-chat-widget.privacy.retention_days');

        if (! is_numeric($days) || (int) $days < 1) {
            return static::withoutGlobalScopes()->whereRaw('1 = 0');
        }

        $cutoff = now()->subDays((int) $days);

        return static::withoutGlobalScopes()
            ->withTrashed()
            ->where(fn (Builder $query) => $query
                ->where('last_message_at', '<', $cutoff)
                ->orWhere(fn (Builder $query) => $query->whereNull('last_message_at')->where('created_at', '<', $cutoff)));
    }

    /**
     * Delete messages explicitly so pruning does not depend on the database
     * enforcing the foreign key cascade.
     */
    protected function pruning(): void
    {
        $this->messages()->delete();
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
