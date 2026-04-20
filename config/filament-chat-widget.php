<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model representing the "owner" of a chat widget. The widget
    | is resolved by this model's `slug` attribute (or configured column).
    | Typical choices are `App\Models\Team`, `App\Models\Site`, or
    | `App\Models\User`. Set to `null` to disable tenant scoping entirely
    | (single-tenant installation).
    |
    */
    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Foreign Key
    |--------------------------------------------------------------------------
    |
    | The column name on the chat_widgets / chat_conversations tables that
    | references the tenant. Defaults to `team_id` for zero-migration upgrades
    | from apps that already have this schema. Change only before installation.
    |
    */
    'tenant_foreign_key' => 'team_id',

    /*
    |--------------------------------------------------------------------------
    | Tenant Slug Column
    |--------------------------------------------------------------------------
    |
    | The column on the tenant model used to resolve widgets publicly.
    |
    */
    'tenant_slug_column' => 'slug',

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolver
    |--------------------------------------------------------------------------
    |
    | A class implementing
    | `Madbox99\FilamentChatWidget\Contracts\ChatWidgetTenantResolver`.
    | Override this if your app needs custom lookup logic (e.g. multi-column
    | slugs or domain-based resolution). Leave `null` to use the default
    | resolver which looks up `tenant_model` by `tenant_slug_column`.
    |
    */
    'tenant_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Assigned Agent Model
    |--------------------------------------------------------------------------
    |
    | The model used for the `assigned_to` relation on conversations.
    | Defaults to the app's User model.
    |
    */
    'agent_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Public API routes for the chat widget. Set `enabled` to false if you
    | want to register routes yourself.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'chat',

        /*
        | Default middleware for chat widget routes. Intentionally DOES NOT
        | include the `web` group: these endpoints are stateless public JSON
        | APIs consumed cross-origin, so session/CSRF middleware would
        | break them. Override only if you know what you're doing.
        */
        'middleware' => [\Madbox99\FilamentChatWidget\Http\Middleware\HandleChatWidgetCors::class],

        'throttle' => [
            'config' => '60,1',
            'start' => '5,1',
            'messages' => '60,1',
            'send' => '20,1',
        ],

        /*
        | Cross-origin resource sharing. The widget is embedded on third-party
        | sites, so the default allows any origin. Restrict by listing
        | specific origins if you want to lock it down.
        */
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With', 'Origin'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JavaScript Widget
    |--------------------------------------------------------------------------
    |
    | URI (relative to APP_URL) where the embeddable widget script is served.
    | Published to the app's `public/` directory via
    | `php artisan vendor:publish --tag=filament-chat-widget-assets`.
    |
    */
    'widget_script_path' => '/vendor/filament-chat-widget/chat-widget.js',

    /*
    |--------------------------------------------------------------------------
    | Privacy / GDPR
    |--------------------------------------------------------------------------
    |
    | The embedded widget is anonymous by default: it does not ask for a name
    | or email, and the visitor IP is NOT stored unless explicitly enabled.
    |
    */
    'privacy' => [
        'store_visitor_ip' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Integration
    |--------------------------------------------------------------------------
    |
    | Configure how the Filament resources behave inside the host panel.
    |
    */
    'filament' => [
        'scoped_to_tenant' => true,
        'navigation_group' => 'Chat',
        'register_chat_widgets_resource' => true,
        'register_chat_conversations_resource' => true,
    ],

];
