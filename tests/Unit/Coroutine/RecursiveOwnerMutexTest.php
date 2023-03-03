<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Coroutine;

use K911\Swoole\Component\Locking\Channel\ChannelMutex;
use K911\Swoole\Component\Locking\RecursiveOwner\RecursiveOwnerMutex;
use PHPUnit\Framework\TestCase;
use Swoole\Coroutine\Scheduler;
use Swoole\Runtime;

final class RecursiveOwnerMutexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Runtime::enableCoroutine();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Runtime::enableCoroutine(false);
    }

    public function testMutexWorks(): void
    {
        $mutex = new RecursiveOwnerMutex(new ChannelMutex());
        $scheduler = new Scheduler();
        $recursiveFn = function (int $testNr) use ($mutex, &$recursiveFn) {
            $mutex->acquire();

            $i = -$testNr;
            usleep(1000);
            self::assertSame(-$testNr, $i);
            $i = $testNr;

            if ($testNr < 1000) {
                $recursiveFn($testNr * 10);
            }

            $mutex->release();
        };

        $scheduler->add(function () use ($recursiveFn) {
            $recursiveFn(1);
        });

        $scheduler->add(function () use ($recursiveFn) {
            $recursiveFn(2);
        });

        $scheduler->add(function () use ($recursiveFn) {
            $recursiveFn(3);
        });

        $scheduler->start();
    }
}
