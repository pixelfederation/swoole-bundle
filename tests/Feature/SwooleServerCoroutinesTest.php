<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine\WaitGroup;

final class SwooleServerCoroutinesTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testCoroutinesWithDebugOn(): void
    {
        $clearCache = $this->createConsoleProcess([
            'cache:clear',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '1', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);
        $clearCache->setTimeout(5);
        $clearCache->disableOutput();
        $clearCache->run();

        $this->assertProcessSucceeded($clearCache);

        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '1', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);

        $serverStart->setTimeout(5);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);
        sleep(2); // wait for swoole init

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $initClient = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($initClient->connect());

            $start = time();
            $wg = new WaitGroup();

            for ($i = 0; $i < 4; ++$i) {
                go(function () use ($wg): void {
                    $wg->add();
                    $client = HttpClient::fromDomain('localhost', 9999, false);
                    $this->assertTrue($client->connect());
                    $response = $client->send('/sleep')['response']; // request sleeps for 2 seconds
                    $this->assertSame(200, $response['statusCode']);
                    $this->assertStringContainsString('text/html', $response['headers']['content-type']);
                    $this->assertStringContainsString('Sleep was fine', $response['body']);
                    $wg->done();
                });
            }

            $wg->wait(10);
            $end = time();

            // without coroutines it should be 8, expected is 2, 1.5s is slowness tolerance in initialization
            self::assertLessThan(3.5, $end - $start);
        });
    }

    public function testCoroutinesWithDebugOff(): void
    {
        $clearCache = $this->createConsoleProcess([
            'cache:clear',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '0', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);
        $clearCache->setTimeout(5);
        $clearCache->disableOutput();
        $clearCache->run();

        $this->assertProcessSucceeded($clearCache);

        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '0', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);

        $serverStart->setTimeout(5);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);
        sleep(2); // wait for swoole init

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $initClient = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($initClient->connect());

            $start = time();
            $wg = new WaitGroup();

            for ($i = 0; $i < 4; ++$i) {
                go(function () use ($wg): void {
                    $wg->add();
                    $client = HttpClient::fromDomain('localhost', 9999, false);
                    $this->assertTrue($client->connect());
                    $response = $client->send('/sleep')['response']; // request sleeps for 2 seconds
                    $this->assertSame(200, $response['statusCode']);
                    $this->assertStringContainsString('text/html', $response['headers']['content-type']);
                    $this->assertStringContainsString('Sleep was fine', $response['body']);
                    $wg->done();
                });
            }

            $wg->wait(10);
            $end = time();

            // without coroutines it should be 8, expected is 2, 1.5s is slowness tolerance in initialization
            self::assertLessThan(3.5, $end - $start);
        });
    }

    public function testCoroutinesWithDoctrineAndWithDebugOff(): void
    {
        $clearCache = $this->createConsoleProcess([
            'cache:clear',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '0', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);
        $clearCache->setTimeout(5);
        $clearCache->disableOutput();
        $clearCache->run();

        $this->assertProcessSucceeded($clearCache);

        $migrations = $this->createConsoleProcess([
            'doctrine:migrations:migrate',
            '--no-interaction',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '0', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);
        $migrations->setTimeout(5);
        $migrations->disableOutput();
        $migrations->run();

        $this->assertProcessSucceeded($migrations);

        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'coroutines', 'APP_DEBUG' => '0', 'WORKER_COUNT' => '1', 'REACTOR_COUNT' => '1']);

        $serverStart->setTimeout(5);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);
        sleep(2); // wait for swoole init

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $initClient = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($initClient->connect());

            $start = time();
            $wg = new WaitGroup();

            for ($i = 0; $i < 10; ++$i) {
                go(function () use ($wg): void {
                    $wg->add();
                    $client = HttpClient::fromDomain('localhost', 9999, false);
                    $this->assertTrue($client->connect());
                    $response = $client->send('/doctrine')['response'];
                    $this->assertSame(200, $response['statusCode']);
                    $this->assertStringContainsString('text/html', $response['headers']['content-type']);
                    $wg->done();
                });
            }

            $wg->wait(10);
            $end = time();

            self::assertLessThan(0.5, $end - $start);
        });
    }
}
