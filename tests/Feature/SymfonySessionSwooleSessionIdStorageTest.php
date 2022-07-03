<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use Symfony\Component\HttpKernel\Kernel;

final class SymfonySessionSwooleSessionIdStorageTest extends SymfonySessionSwooleSessionStorageTest
{
    public function setUp(): void
    {
        parent::setUp();
        if (version_compare(Kernel::VERSION, '6.0.0', '>=')) {
            $this->markTestSkipped('Test not applicable for Symfony versions 6+.');
        }
        $this->testingAppEnv = 'session';
    }
}
