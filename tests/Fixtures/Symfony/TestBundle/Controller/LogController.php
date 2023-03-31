<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Logging\InMemoryLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LogController
{
    /**
     * @Route(
     *     methods={"GET"},
     *     path="/logs"
     * )
     */
    public function getLogs(): Response
    {
        return new Response(implode(PHP_EOL, InMemoryLogger::getAndClear()), 200);
    }

    /**
     * @Route(
     *     methods={"GET"},
     *     path="/monologs"
     * )
     */
    public function monologs(LoggerInterface $phpLogger): JsonResponse
    {
        $phpLogger->info('Test app message.');

        return new JsonResponse(['hello' => 'world!'], 200);
    }
}
