<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Models;

use Cegem360\FilamentChatWidget\Database\Factories\ChatWidgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class ChatWidget extends Model
{
    /** @use HasFactory<ChatWidgetFactory> */
    use HasFactory;

    protected $table = 'chat_widgets';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected static function newFactory(): ChatWidgetFactory
    {
        return ChatWidgetFactory::new();
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
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'business_hours' => 'array',
        ];
    }
}
