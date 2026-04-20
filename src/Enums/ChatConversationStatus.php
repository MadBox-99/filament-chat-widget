<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChatConversationStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => __('filament-chat-widget::chat.status.open'),
            self::Pending => __('filament-chat-widget::chat.status.pending'),
            self::Closed => __('filament-chat-widget::chat.status.closed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Open => 'success',
            self::Pending => 'warning',
            self::Closed => 'gray',
        };
    }
}
