<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget;

use Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Madbox99\FilamentChatWidget\Support\EloquentTenantResolver;
use Illuminate\Support\ServiceProvider;

final class FilamentChatWidgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-chat-widget.php',
            'filament-chat-widget'
        );

        $this->app->singleton(ChatWidgetTenantResolver::class, function ($app) {
            /** @var class-string<ChatWidgetTenantResolver>|null $override */
            $override = config('filament-chat-widget.tenant_resolver');

            if ($override !== null && class_exists($override)) {
                return $app->make($override);
            }

            /** @var class-string|null $tenantModel */
            $tenantModel = config('filament-chat-widget.tenant_model');
            $slugColumn = (string) config('filament-chat-widget.tenant_slug_column', 'slug');

            return new EloquentTenantResolver($tenantModel, $slugColumn);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/filament-chat-widget.php' => config_path('filament-chat-widget.php'),
        ], 'filament-chat-widget-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'filament-chat-widget-migrations');

        $this->publishes([
            __DIR__ . '/../resources/js/chat-widget.js' => public_path('vendor/filament-chat-widget/chat-widget.js'),
        ], 'filament-chat-widget-assets');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-chat-widget');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');

        $this->publishes([
            __DIR__ . '/../resources/lang/' => lang_path('vendor/filament-chat-widget'),
        ], 'filament-chat-widget-translations');

        if ((bool) config('filament-chat-widget.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}
