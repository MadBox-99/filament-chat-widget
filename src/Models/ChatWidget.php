<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Database\Factories\ChatWidgetFactory;
use Madbox99\FilamentChatWidget\Enums\ChatWeekday;
use Madbox99\FilamentChatWidget\Support\EloquentTenantResolver;
use Override;

/**
 * @property int $id
 * @property string $title
 * @property string|null $welcome_message
 * @property string $color
 * @property string $position
 * @property bool $is_active
 * @property string|null $offline_message
 * @property array<string, string>|null $business_hours
 * @property array<int, mixed>|null $opening_hours Entries of {day, from, to}; validated at read time.
 * @property string|null $timezone
 * @property string|null $auto_reply_message
 * @property string|null $custom_css
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChatWidget extends Model
{
    /** @use HasFactory<ChatWidgetFactory> */
    use HasFactory;

    /**
     * Public slug used by the embed snippet when no tenant model is configured.
     */
    public const SINGLE_TENANT_SLUG = 'default';

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
     * Single-tenant installations have neither a tenant model nor a custom resolver.
     */
    public static function isSingleTenant(): bool
    {
        if (config('filament-chat-widget.tenant_model') !== null
            || config('filament-chat-widget.tenant_resolver') !== null) {
            return false;
        }

        // An app may also bind its own resolver in a service provider.
        return app(ChatWidgetTenantResolver::class) instanceof EloquentTenantResolver;
    }

    /**
     * Whether an agent is expected to be available at the given moment.
     *
     * Without configured opening hours the widget is always online. Each
     * opening hours entry is `{day: mon..sun, from: HH:MM, to: HH:MM}` and is
     * evaluated in the widget's timezone (falling back to the app timezone).
     */
    public function isOnlineAt(CarbonInterface $moment): bool
    {
        /** @var list<array{day?: string, from?: string, to?: string}> $ranges */
        $ranges = array_values(array_filter(
            (array) $this->opening_hours,
            fn (mixed $range): bool => is_array($range) && isset($range['day'], $range['from'], $range['to']),
        ));

        if ($ranges === []) {
            return true;
        }

        $local = $moment->copy()->setTimezone($this->resolveTimezone());
        $today = ChatWeekday::fromDate($local)->value;
        $yesterday = ChatWeekday::fromDate($local->copy()->subDay())->value;
        $time = $local->format('H:i');

        foreach ($ranges as $range) {
            // Time pickers may store seconds ("09:00:00"); compare on HH:MM only.
            $from = substr((string) $range['from'], 0, 5);
            $to = substr((string) $range['to'], 0, 5);

            if ($from < $to) {
                if ($range['day'] === $today && $time >= $from && $time < $to) {
                    return true;
                }

                continue;
            }

            // Ranges past midnight (e.g. fri 22:00–02:00) cover the evening of
            // their own day and the early hours of the next one.
            if (($range['day'] === $today && $time >= $from)
                || ($range['day'] === $yesterday && $time < $to)) {
                return true;
            }
        }

        return false;
    }

    private function resolveTimezone(): string
    {
        $timezone = $this->timezone;

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return (string) config('app.timezone', 'UTC');
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
            'opening_hours' => 'array',
        ];
    }
}
