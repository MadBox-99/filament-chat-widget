<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders chat message text for the admin feed: escapes it, turns web and
 * e-mail addresses into links and recognises YouTube / Vimeo videos.
 * The embeddable widget does the same in resources/js/chat-widget.js.
 */
final class MessageFormatter
{
    private const string LINK_PATTERN = '~(https?://[^\s<>"]+[^\s<>".,;:!?)\]\'»”])|(www\.[^\s<>"]+[^\s<>".,;:!?)\]\'»”])|([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})~u';

    /**
     * Escape the text and turn URLs, "www." addresses and e-mail addresses into links.
     */
    public static function linkify(?string $text): HtmlString
    {
        $html = '';

        foreach (self::tokens((string) $text) as $token) {
            $html .= match ($token['type']) {
                'url' => self::anchor($token['value'], $token['value'], true),
                'www' => self::anchor('https://'.$token['value'], $token['value'], true),
                'email' => self::anchor('mailto:'.$token['value'], $token['value'], false),
                default => e($token['value']),
            };
        }

        return new HtmlString($html);
    }

    /**
     * The distinct YouTube and Vimeo videos linked in the text, in order.
     *
     * @return list<array{provider: string, id: string, url: string, embed_url: string}>
     */
    public static function videos(?string $text): array
    {
        $videos = [];

        foreach (self::tokens((string) $text) as $token) {
            if ($token['type'] === 'text' || $token['type'] === 'email') {
                continue;
            }

            $url = $token['type'] === 'www' ? 'https://'.$token['value'] : $token['value'];
            $video = self::video($url);

            if ($video !== null) {
                $videos[$video['embed_url']] = $video;
            }
        }

        return array_values($videos);
    }

    /**
     * @return array{provider: string, id: string, url: string, embed_url: string}|null
     */
    public static function video(string $url): ?array
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $host = (string) preg_replace('/^(www\.|m\.)/', '', $host);

        if ($host === 'youtu.be' && preg_match('~^/([A-Za-z0-9_-]{11})~', $path, $match)) {
            return self::youtube($match[1], $url);
        }

        if ($host === 'youtube.com' || $host === 'youtube-nocookie.com') {
            parse_str((string) ($parts['query'] ?? ''), $query);

            if ($path === '/watch' && is_string($query['v'] ?? null) && preg_match('/^[A-Za-z0-9_-]{11}$/', $query['v'])) {
                return self::youtube($query['v'], $url);
            }

            if (preg_match('~^/(?:shorts|embed|live)/([A-Za-z0-9_-]{11})~', $path, $match)) {
                return self::youtube($match[1], $url);
            }
        }

        if ($host === 'vimeo.com' && preg_match('~^/(?:channels/[^/]+/)?(\d+)(?:/([0-9a-f]+))?~', $path, $match)) {
            return self::vimeo($match[1], $match[2] ?? null, $url);
        }

        if ($host === 'player.vimeo.com' && preg_match('~^/video/(\d+)~', $path, $match)) {
            parse_str((string) ($parts['query'] ?? ''), $query);

            return self::vimeo($match[1], is_string($query['h'] ?? null) ? $query['h'] : null, $url);
        }

        return null;
    }

    /**
     * @return list<array{type: 'text'|'url'|'www'|'email', value: string}>
     */
    private static function tokens(string $text): array
    {
        $tokens = [];
        $offset = 0;

        preg_match_all(self::LINK_PATTERN, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        foreach ($matches as $match) {
            [$value, $position] = $match[0];

            if ($position > $offset) {
                $tokens[] = ['type' => 'text', 'value' => substr($text, $offset, $position - $offset)];
            }

            $tokens[] = [
                'type' => match (true) {
                    $match[1][0] !== null => 'url',
                    $match[2][0] !== null => 'www',
                    default => 'email',
                },
                'value' => $value,
            ];

            $offset = $position + strlen($value);
        }

        if ($offset < strlen($text)) {
            $tokens[] = ['type' => 'text', 'value' => substr($text, $offset)];
        }

        return $tokens;
    }

    private static function anchor(string $href, string $label, bool $newTab): string
    {
        return sprintf(
            '<a href="%s"%s style="color:inherit;text-decoration:underline">%s</a>',
            e($href),
            $newTab ? ' target="_blank" rel="noopener noreferrer nofollow"' : '',
            e($label),
        );
    }

    /**
     * @return array{provider: string, id: string, url: string, embed_url: string}
     */
    private static function youtube(string $id, string $url): array
    {
        return ['provider' => 'youtube', 'id' => $id, 'url' => $url, 'embed_url' => 'https://www.youtube-nocookie.com/embed/'.$id];
    }

    /**
     * @return array{provider: string, id: string, url: string, embed_url: string}
     */
    private static function vimeo(string $id, ?string $hash, string $url): array
    {
        $embedUrl = 'https://player.vimeo.com/video/'.$id.($hash !== null && $hash !== '' ? '?h='.rawurlencode($hash) : '');

        return ['provider' => 'vimeo', 'id' => $id, 'url' => $url, 'embed_url' => $embedUrl];
    }
}
