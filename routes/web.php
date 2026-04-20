<?php

declare(strict_types=1);

use Cegem360\FilamentChatWidget\Http\Controllers\ChatWidgetController;
use Cegem360\FilamentChatWidget\Http\Middleware\HandleChatWidgetCors;
use Illuminate\Support\Facades\Route;

$prefix = (string) config('filament-chat-widget.routes.prefix', 'chat');
$middleware = (array) config('filament-chat-widget.routes.middleware', [HandleChatWidgetCors::class]);
$throttle = (array) config('filament-chat-widget.routes.throttle', []);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () use ($throttle): void {
        Route::get('widget/{slug}', [ChatWidgetController::class, 'config'])
            ->middleware('throttle:' . ($throttle['config'] ?? '60,1'))
            ->name('chat.widget.config');

        Route::post('conversations', [ChatWidgetController::class, 'start'])
            ->middleware('throttle:' . ($throttle['start'] ?? '5,1'))
            ->name('chat.conversation.start');

        Route::get('conversations/{uuid}/messages', [ChatWidgetController::class, 'messages'])
            ->middleware('throttle:' . ($throttle['messages'] ?? '60,1'))
            ->name('chat.conversation.messages');

        Route::post('conversations/{uuid}/messages', [ChatWidgetController::class, 'sendMessage'])
            ->middleware('throttle:' . ($throttle['send'] ?? '20,1'))
            ->name('chat.conversation.send');

        Route::options('widget/{slug}', fn () => response('', 204));
        Route::options('conversations', fn () => response('', 204));
        Route::options('conversations/{uuid}/messages', fn () => response('', 204));
    });
