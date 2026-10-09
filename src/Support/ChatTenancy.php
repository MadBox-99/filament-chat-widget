<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final class ChatTenancy
{
    /**
     * The tenant model: `tenant_model` from the config, or — when that is not
     * set — the tenant model of the current (or default) Filament panel, so
     * apps that never published the config still get correct tenant scoping.
     *
     * @return class-string<Model>|null
     */
    public static function model(): ?string
    {
        $configured = config('filament-chat-widget.tenant_model');

        if (is_string($configured) && $configured !== '') {
            /** @var class-string<Model> $configured */
            return $configured;
        }

        try {
            $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
        } catch (Throwable) {
            return null;
        }

        return $panel->hasTenancy() ? $panel->getTenantModel() : null;
    }

    public static function foreignKey(): string
    {
        return (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');
    }
}
