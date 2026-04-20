# Filament Chat Widget

Embeddable live-chat widget for Laravel with a [Filament v5](https://filamentphp.com/) admin panel.

Drop a `<script>` tag into any HTML page (WordPress, static site, SPA) and manage incoming conversations from your Filament admin.

## Features

- Filament v5 resources for widget configuration and conversation management
- Vanilla JS embed (no framework dependencies on the host page)
- Public JSON API for widget config, conversation start, polling, and message sending
- Pluggable multi-tenancy — works with `Team`, `Site`, `User`, or no tenant at all
- Rate-limited routes out of the box
- English and Hungarian translations included

## Installation

```bash
composer require madbox-99/filament-chat-widget
php artisan vendor:publish --tag=filament-chat-widget-config
php artisan migrate
```

The embeddable JS is served directly from the `vendor/` directory by a
Laravel route (`/chat/embed.js`), so there is **no asset publish step**.
New package versions propagate to already-embedded pages automatically
via ETag revalidation.

## Configuration

Set your tenant model in `config/filament-chat-widget.php`:

```php
'tenant_model' => \App\Models\Team::class,
'tenant_foreign_key' => 'team_id',
'tenant_slug_column' => 'slug',
```

Register the plugin in your Filament panel provider:

```php
use Madbox99\FilamentChatWidget\FilamentChatWidgetPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentChatWidgetPlugin::make(),
        ]);
}
```

## Embed the widget

From the widget edit page in the Filament admin, copy the embed snippet:

```html
<script src="https://your-app.test/chat/embed.js"
        data-team="{tenant_slug}" async></script>
```

Paste it just before the closing `</body>` tag of any page.

## Cross-origin (CORS)

The chat routes ship with CORS enabled by default for **all origins** (`*`),
since the widget is designed to be embedded on third-party sites. To restrict
which domains can talk to your chat API, edit
`config/filament-chat-widget.php`:

```php
'routes' => [
    'cors' => [
        'allowed_origins' => ['https://example.com', 'https://blog.example.com'],
    ],
],
```

The package routes deliberately **do not** use Laravel's `web` middleware group.
They are stateless public JSON APIs, so session/CSRF middleware would break
them. **You do not need to add CSRF exemptions** to `bootstrap/app.php`.

## Custom tenant resolver

If the default Eloquent slug lookup isn't enough (e.g. domain-based resolution), implement `Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver` and set:

```php
'tenant_resolver' => \App\Support\MyTenantResolver::class,
```

## License

MIT
