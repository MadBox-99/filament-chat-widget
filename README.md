# Filament Chat Widget

Embeddable live-chat widget for Laravel with a [Filament v5](https://filamentphp.com/) admin panel.

Drop a `<script>` tag into any HTML page (WordPress, static site, SPA) and manage incoming conversations from your Filament admin.

## Features

- Filament v4/v5 resources for widget configuration and conversation management
- Chat-style agent feed with close / reopen / assign-to-me actions and an unread navigation badge
- Vanilla JS embed (no framework dependencies on the host page), isolated from host CSS
- Unread badge on the chat button, background polling that pauses in hidden tabs
- Opening hours with timezone: outside them visitors see the offline message
- Widget labels localized from the host page language (English and Hungarian included)
- Pluggable multi-tenancy — works with `Team`, `Site`, `User`, or no tenant at all
- GDPR-friendly: anonymous by default, IP not stored, optional automatic retention pruning
- Rate-limited routes and built-in CORS

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

Optional attributes on the `<script>` tag:

| Attribute     | Purpose                                                                 |
|---------------|-------------------------------------------------------------------------|
| `data-locale` | Label language (`hu`, `en`, …). Defaults to `<html lang>`, then the browser. |
| `data-prefix` | Route prefix, if you changed `routes.prefix` in the config.             |

### Single-tenant installations

Leave `tenant_model` (and `tenant_resolver`) as `null`. The panel then manages a
single widget and the embed snippet uses `data-team="default"`.

## Opening hours

On the widget edit page you can add opening hours (day + from/to) and a
timezone. Without opening hours the widget is always shown as online. Outside
the configured hours visitors see the offline message but can still leave a
message.

## Data retention (GDPR)

Conversations are anonymous by default and the visitor IP is not stored. To
delete inactive conversations automatically, set a retention period:

```php
'privacy' => [
    'store_visitor_ip' => false,
    'retention_days' => 180,
],
```

The package then schedules `model:prune` for chat conversations daily. Your app
must run the Laravel scheduler (`php artisan schedule:run` every minute).

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

## Development

```bash
composer test      # Pest
composer lint      # Pint
composer analyse   # PHPStan (Larastan, level 6)
```

## License

MIT
