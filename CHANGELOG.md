# Changelog

## Unreleased

### Security
- The agent chat feed locks its conversation id and authorizes it through the
  resource query and policy on mount. Previously a tampered Livewire request
  could read or reply to another tenant's conversation.
- CORS no longer sends `Access-Control-Allow-Credentials` and no longer echoes
  an allowed origin to disallowed origins.

### Fixed
- Single-tenant mode (`tenant_model = null`) works: the widget is served under
  the `default` slug instead of always returning 404.
- The widget no longer shows duplicate messages when a poll and a send overlap.
- A failed send keeps the typed text and shows an error instead of silently
  losing the message.
- The unread counter is incremented atomically.
- A visitor message reopens a closed conversation.
- Creating a second widget for the same tenant is blocked in the UI instead of
  failing with a database error.

### Added
- Opening hours (day + from/to, ranges may run past midnight) with timezone; `is_online` in the config
  endpoint and an offline notice in the widget.
- Localized widget labels (`labels` in the config endpoint, `?locale=` /
  `data-locale`).
- Unread badge on the chat button with background polling that pauses in
  hidden tabs; Escape closes the panel; better ARIA labels.
- Close / reopen / assign-to-me actions on the conversation page; replying
  auto-assigns unassigned conversations.
- Unread conversations navigation badge.
- `privacy.retention_days`: automatic pruning of inactive conversations.
- Pest test suite, PHPStan (level 6), GitHub Actions CI.

### Removed
- The legacy messages relation manager (replaced by the chat feed in 0.3).
- The stale `widget_script_path` config key and `filament-chat-widget-assets`
  publish tag (the script is served by the `/chat/embed.js` route).

### Upgrade
- Embed snippets that still point at the old published file
  (`/vendor/filament-chat-widget/chat-widget.js`) keep working against the new
  API but never receive updates. Switch them to `/chat/embed.js` (copy the
  snippet again from the widget edit page).
- Visitor messages now update the conversation with a regular `update()` after
  an atomic SQL increment, so `saving`/`saved` observers keep firing.
- Run `php artisan migrate` (adds `opening_hours` and `timezone` to
  `chat_widgets`). The old free-text business hours field stays visible on the
  edit page while it contains data, so it can be moved over by hand.
