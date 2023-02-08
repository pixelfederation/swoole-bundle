<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NoOpStreamedResponseProcessor implements ResponseProcessorInterface
{
    public function __construct(private ResponseProcessorInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse): void
    {
        if ($httpFoundationResponse instanceof StreamedResponse) {
            return;
        }

        $this->decorated->process($httpFoundationResponse, $swooleResponse);
    }
}
