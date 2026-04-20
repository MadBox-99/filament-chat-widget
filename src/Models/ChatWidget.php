<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Models;

use Cegem360\FilamentChatWidget\Database\Factories\ChatWidgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
