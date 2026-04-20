<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HandleChatWidgetCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $headers = $this->corsHeaders($request->headers->get('Origin'));

        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $headers);
        }

        /** @var Response $response */
        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function corsHeaders(?string $origin): array
    {
        /** @var list<string> $allowedOrigins */
        $allowedOrigins = (array) config('filament-chat-widget.routes.cors.allowed_origins', ['*']);

        $allowAll = in_array('*', $allowedOrigins, true);
        $allowOrigin = $allowAll
            ? '*'
            : ($origin !== null && in_array($origin, $allowedOrigins, true) ? $origin : (string) ($allowedOrigins[0] ?? ''));

        /** @var list<string> $allowedHeaders */
        $allowedHeaders = (array) config(
            'filament-chat-widget.routes.cors.allowed_headers',
            ['Content-Type', 'Accept', 'X-Requested-With', 'Origin']
        );

        $headers = [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => implode(', ', $allowedHeaders),
            'Access-Control-Max-Age' => '86400',
            'Vary' => 'Origin',
        ];

        if (! $allowAll && $allowOrigin !== '') {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $headers;
    }
}
