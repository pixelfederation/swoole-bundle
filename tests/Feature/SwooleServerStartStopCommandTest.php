<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\Exception\ClientConnectionErrorException;
use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerStartStopCommandTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
        $this->deleteVarDirectory();
    }

    public function testStartCallStop(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $this->assertHelloWorldRequestSucceeded($client);
        });
    }

    /**
     * If monolog path `php://stdout` in handler is used, seems stdout is still open in test even no option `--open-console`
     * is used (and server console output is closed).
     */
    public function testStartWithOpenConsole(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            '--open-console',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->enableOutput();
        $serverStart->start();

        $this->assertTrue($serverStart->isStarted());

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $response = $client->send('/monologs')['response'];
            self::assertSame(200, $response['statusCode']);
            self::assertSame([
                'hello' => 'world!',
            ], $response['body']);
        });

        $errorOutput = $serverStart->getOutput();
        $this->assertStringContainsString('php.INFO: Test app message.', $errorOutput);
    }

    public function testStartCallStopOnReactorRunningMode(): void
    {
        $envs = ['APP_ENV' => 'reactor'];
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], $envs);

        $serverStart->setTimeout(3);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function () use ($envs): void {
            $this->deferServerStop([], $envs);

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $this->assertHelloWorldRequestSucceeded($client);
        });
    }

    public function testNoDelayShutdown(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->disableOutput();
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            go(function () {
                $client = HttpClient::fromDomain('localhost', 9999, false);
                $this->assertTrue($client->connect());

                try {
                    $response = $client->send('/dummy-sleep')['response'];
                    $this->assertSame(200, $response['statusCode']);
                    $this->fail('Server was not shutdown by kill (no-delay).');
                } catch (ClientConnectionErrorException $e) {
                    // exception thrown, request was not finished, no-delay server shutdown
                    $this->assertStringContainsStringIgnoringCase('Server Reset', $e->getMessage());
                }
            });
            go(function () {
                // wait for $client to do request
                \co::sleep(1);
                $this->serverStop(['--no-delay']);
            });
        });
    }
}
