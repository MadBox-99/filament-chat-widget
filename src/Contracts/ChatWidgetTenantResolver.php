<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Contracts;

interface ChatWidgetTenantResolver
{
    /**
     * Resolve a tenant primary key (the value stored in the
     * `tenant_foreign_key` column) from a public slug.
     */
    public function resolveTenantKeyBySlug(string $slug): int|string|null;

    /**
     * Resolve a public slug from a given tenant key. Used by the Filament
     * admin UI to build embed snippets.
     */
    public function resolveSlugByTenantKey(int|string $tenantKey): ?string;
}
