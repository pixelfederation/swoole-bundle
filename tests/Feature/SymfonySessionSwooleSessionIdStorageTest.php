<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use Symfony\Component\HttpKernel\Kernel;

final class SymfonySessionSwooleSessionIdStorageTest extends SymfonySessionSwooleSessionStorageTest
{
    public function setUp(): void
    {
        parent::setUp();
        // We need to ignore the check below in PHPStan. It's based on the context of the Symfony version installed.
        // @phpstan-ignore-next-line
        if (version_compare(Kernel::VERSION, '6.0.0', '>=')) {
            $this->markTestSkipped('Test not applicable for Symfony versions 6+.');
        }
        // @phpstan-ignore-next-line
        $this->testingAppEnv = 'session';
    }
}
