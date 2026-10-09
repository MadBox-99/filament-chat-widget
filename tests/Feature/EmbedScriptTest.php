<?php

declare(strict_types=1);

it('serves the embed script with an ETag', function (): void {
    $response = $this->get('/chat/embed.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

    expect($response->headers->get('ETag'))->not->toBeEmpty();
});

it('returns 304 when the ETag matches', function (): void {
    $etag = $this->get('/chat/embed.js')->headers->get('ETag');

    $this->get('/chat/embed.js', ['If-None-Match' => $etag])->assertStatus(304);
});
