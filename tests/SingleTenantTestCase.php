<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Tests;

use Madbox99\FilamentChatWidget\Tests\Fixtures\AdminPanelProvider;

/**
 * An app without a tenant model and without a tenancy-enabled panel.
 */
abstract class SingleTenantTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_values(array_filter(
            parent::getPackageProviders($app),
            fn (string $provider): bool => $provider !== AdminPanelProvider::class,
        ));
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filament-chat-widget.tenant_model', null);
    }
}
