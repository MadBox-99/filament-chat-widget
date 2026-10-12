<?php

declare(strict_types=1);

use Madbox99\FilamentChatWidget\Support\MessageFormatter;

it('links urls, www addresses and e-mail addresses without trailing punctuation', function (): void {
    expect(MessageFormatter::linkify('Lásd https://example.com/a?b=1&c=2, www.cegem360.hu vagy írj: info@cegem360.hu.')->toHtml())
        ->toBe('Lásd <a href="https://example.com/a?b=1&amp;c=2" target="_blank" rel="noopener noreferrer nofollow" style="color:inherit;text-decoration:underline">https://example.com/a?b=1&amp;c=2</a>, '
            .'<a href="https://www.cegem360.hu" target="_blank" rel="noopener noreferrer nofollow" style="color:inherit;text-decoration:underline">www.cegem360.hu</a> vagy írj: '
            .'<a href="mailto:info@cegem360.hu" style="color:inherit;text-decoration:underline">info@cegem360.hu</a>.');
});

it('escapes everything that is not a link', function (): void {
    expect(MessageFormatter::linkify('<script>alert(1)</script> javascript:alert(1) "x"')->toHtml())
        ->toBe('&lt;script&gt;alert(1)&lt;/script&gt; javascript:alert(1) &quot;x&quot;');
});

it('does not let a quote break out of the link', function (): void {
    expect(MessageFormatter::linkify('https://example.com/"onmouseover="alert(1)')->toHtml())
        ->toStartWith('<a href="https://example.com/"')
        ->not->toContain('" onmouseover');
});

it('recognises youtube and vimeo links', function (string $url, string $provider, string $embedUrl): void {
    expect(MessageFormatter::video($url))->toMatchArray(['provider' => $provider, 'embed_url' => $embedUrl]);
})->with([
    'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'vimeo' => ['https://vimeo.com/76979871', 'vimeo', 'https://player.vimeo.com/video/76979871'],
    'unlisted vimeo' => ['https://vimeo.com/76979871/8272103f6e', 'vimeo', 'https://player.vimeo.com/video/76979871?h=8272103f6e'],
    'vimeo player' => ['https://player.vimeo.com/video/76979871', 'vimeo', 'https://player.vimeo.com/video/76979871'],
]);

it('ignores links that are not videos', function (string $url): void {
    expect(MessageFormatter::video($url))->toBeNull();
})->with([
    ['https://example.com/watch?v=dQw4w9WgXcQ'],
    ['https://www.youtube.com/@cegem360'],
    ['https://www.youtube.com/watch?v=short'],
    ['https://vimeo.com/channels/staffpicks'],
]);

it('lists each video in the text once', function (): void {
    $videos = MessageFormatter::videos('Nézd: https://youtu.be/dQw4w9WgXcQ és www.youtube.com/watch?v=dQw4w9WgXcQ, meg https://vimeo.com/76979871');

    expect(array_column($videos, 'embed_url'))->toBe([
        'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        'https://player.vimeo.com/video/76979871',
    ]);
});
