<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

enum ChatWeekday: string implements HasLabel
{
    case Monday = 'mon';
    case Tuesday = 'tue';
    case Wednesday = 'wed';
    case Thursday = 'thu';
    case Friday = 'fri';
    case Saturday = 'sat';
    case Sunday = 'sun';

    public static function fromDate(CarbonInterface $date): self
    {
        return self::cases()[$date->dayOfWeekIso - 1];
    }

    public function getLabel(): string
    {
        return __('filament-chat-widget::chat.weekdays.'.$this->value);
    }
}
