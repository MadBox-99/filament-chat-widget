<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Madbox99\FilamentChatWidget\Http\Controllers\ChatWidgetController;
use Madbox99\FilamentChatWidget\Http\Controllers\EmbedScriptController;
use Madbox99\FilamentChatWidget\Http\Middleware\HandleChatWidgetCors;

$prefix = (string) config('filament-chat-widget.routes.prefix', 'chat');
$middleware = (array) config('filament-chat-widget.routes.middleware', [HandleChatWidgetCors::class]);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function (): void {
        Route::get('embed.js', EmbedScriptController::class)
            ->middleware('throttle:chat-widget-embed')
            ->name('chat.embed.script');

        Route::get('widget/{slug}', [ChatWidgetController::class, 'config'])
            ->middleware('throttle:chat-widget-config')
            ->name('chat.widget.config');

        Route::post('conversations', [ChatWidgetController::class, 'start'])
            ->middleware('throttle:chat-widget-start')
            ->name('chat.conversation.start');

        Route::get('conversations/{uuid}/messages', [ChatWidgetController::class, 'messages'])
            ->middleware('throttle:chat-widget-messages')
            ->name('chat.conversation.messages');

        Route::post('conversations/{uuid}/messages', [ChatWidgetController::class, 'sendMessage'])
            ->middleware('throttle:chat-widget-send')
            ->name('chat.conversation.send');

        Route::options('widget/{slug}', fn () => response('', 204));
        Route::options('conversations', fn () => response('', 204));
        Route::options('conversations/{uuid}/messages', fn () => response('', 204));
    });
