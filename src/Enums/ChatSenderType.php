<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Enums;

use Filament\Support\Contracts\HasLabel;

enum ChatSenderType: string implements HasLabel
{
    case Visitor = 'visitor';
    case Agent = 'agent';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Visitor => __('filament-chat-widget::chat.sender.visitor'),
            self::Agent => __('filament-chat-widget::chat.sender.agent'),
            self::System => __('filament-chat-widget::chat.sender.system'),
        };
    }
}
