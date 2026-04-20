<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EmbedScriptController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $path = dirname(__DIR__, 3) . '/resources/js/chat-widget.js';

        if (! is_file($path)) {
            throw new NotFoundHttpException();
        }

        $hash = md5_file($path);
        $etag = '"' . $hash . '"';
        $mtime = (int) filemtime($path);
        $lastModified = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600, must-revalidate',
            'ETag' => $etag,
            'Last-Modified' => $lastModified,
            'X-Widget-Version' => $hash,
        ];

        if ($request->headers->get('If-None-Match') === $etag) {
            return new Response('', 304, $headers);
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($ifModifiedSince !== null && strtotime($ifModifiedSince) >= $mtime) {
            return new Response('', 304, $headers);
        }

        $contents = (string) file_get_contents($path);

        return new Response($contents, 200, $headers);
    }
}
