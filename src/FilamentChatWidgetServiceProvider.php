<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Livewire\ChatConversationFeed;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Support\ChatTenancy;
use Madbox99\FilamentChatWidget\Support\EloquentTenantResolver;

final class FilamentChatWidgetServiceProvider extends ServiceProvider
{
    private const array DEFAULT_THROTTLE = [
        'embed' => '120,1',
        'config' => '60,1',
        'start' => '5,1',
        'messages' => '120,1',
        'send' => '20,1',
    ];

    private const array DEFAULT_DAILY_LIMITS = [
        'start' => 10,
        'send' => 200,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-chat-widget.php',
            'filament-chat-widget'
        );

        $this->app->singleton(ChatWidgetTenantResolver::class, function ($app) {
            /** @var class-string<ChatWidgetTenantResolver>|null $override */
            $override = config('filament-chat-widget.tenant_resolver');

            if ($override !== null && class_exists($override)) {
                return $app->make($override);
            }

            $slugColumn = (string) config('filament-chat-widget.tenant_slug_column', 'slug');

            return new EloquentTenantResolver(ChatTenancy::model(), $slugColumn);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/filament-chat-widget.php' => config_path('filament-chat-widget.php'),
        ], 'filament-chat-widget-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'filament-chat-widget-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-chat-widget');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-chat-widget');

        Livewire::component('filament-chat-widget.chat-conversation-feed', ChatConversationFeed::class);

        $this->publishes([
            __DIR__.'/../resources/lang/' => lang_path('vendor/filament-chat-widget'),
        ], 'filament-chat-widget-translations');

        if ((bool) config('filament-chat-widget.routes.enabled', true)) {
            $this->registerRateLimiters();
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('filament-chat-widget.privacy.retention_days') !== null) {
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('model:prune', ['--model' => [ChatConversation::class]])
                    ->daily()
                    ->name('filament-chat-widget:prune-conversations')
                    ->withoutOverlapping();
            });
        }
    }

    /**
     * One named limiter per endpoint, keyed by endpoint and IP, so the
     * counters stay separate. start and send also get a daily per-IP cap.
     */
    private function registerRateLimiters(): void
    {
        foreach (self::DEFAULT_THROTTLE as $name => $default) {
            RateLimiter::for('chat-widget-'.$name, function (Request $request) use ($name, $default): array {
                $key = 'chat-widget-'.$name.':'.$request->ip();
                [$max, $minutes] = array_map(intval(...), explode(',', (string) config('filament-chat-widget.routes.throttle.'.$name, $default)) + [1 => 1]);

                $limits = [Limit::perMinutes(max(1, $minutes), max(1, $max))->by($key)];

                $daily = config('filament-chat-widget.routes.daily_limits.'.$name, self::DEFAULT_DAILY_LIMITS[$name] ?? null);

                if (is_numeric($daily)) {
                    $limits[] = Limit::perDay(max(1, (int) $daily))->by($key.':day');
                }

                return $limits;
            });
        }
    }
}
