<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use K911\Swoole\Bridge\Symfony\Container\CoWrapper;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Runtime;

final class CoroutineHttpKernelRequestHandler implements RequestHandlerInterface
{
    private $decorated;
    private $coWrapper;

    /**
     * @var bool
     */
    private $wereCoroutinesEnabled = false;

    public function __construct(RequestHandlerInterface $decorated, CoWrapper $coWrapper)
    {
        $this->decorated = $decorated;
        $this->coWrapper = $coWrapper;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $this->enableCoroutines();
        $this->coWrapper->defer();
        $this->decorated->handle($request, $response);
    }

    private function enableCoroutines(): void
    {
        if ($this->wereCoroutinesEnabled) {
            return;
        }

        $this->wereCoroutinesEnabled = true;
        Runtime::enableCoroutine();
    }
}
