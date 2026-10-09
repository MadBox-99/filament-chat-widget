<?php

declare(strict_types=1);

use Madbox99\FilamentChatWidget\Tests\SingleTenantTestCase;
use Madbox99\FilamentChatWidget\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

pest()->extend(SingleTenantTestCase::class)->in('SingleTenant');
