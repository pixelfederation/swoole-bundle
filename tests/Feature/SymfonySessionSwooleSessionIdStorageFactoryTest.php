<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

final class SymfonySessionSwooleSessionIdStorageFactoryTest extends SymfonySessionSwooleSessionStorageTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->testingAppEnv = 'session_factory';
    }
}
