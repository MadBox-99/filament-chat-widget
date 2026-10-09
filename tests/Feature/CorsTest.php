<?php

declare(strict_types=1);

it('answers preflight requests with wildcard CORS by default', function (): void {
    $this->call('OPTIONS', '/chat/conversations', server: ['HTTP_ORIGIN' => 'https://site.test'])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

it('echoes an allowed origin when origins are restricted', function (): void {
    config()->set('filament-chat-widget.routes.cors.allowed_origins', ['https://site.test']);

    $this->call('OPTIONS', '/chat/conversations', server: ['HTTP_ORIGIN' => 'https://site.test'])
        ->assertHeader('Access-Control-Allow-Origin', 'https://site.test')
        ->assertHeader('Vary', 'Origin');
});

it('does not send an allow-origin header to a disallowed origin', function (): void {
    config()->set('filament-chat-widget.routes.cors.allowed_origins', ['https://site.test']);

    $this->call('OPTIONS', '/chat/conversations', server: ['HTTP_ORIGIN' => 'https://evil.test'])
        ->assertHeaderMissing('Access-Control-Allow-Origin')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');
});
