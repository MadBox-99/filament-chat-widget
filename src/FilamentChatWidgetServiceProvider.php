<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Livewire\ChatConversationFeed;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Support\ChatTenancy;
use Madbox99\FilamentChatWidget\Support\EloquentTenantResolver;

final class FilamentChatWidgetServiceProvider extends ServiceProvider
{
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
}
