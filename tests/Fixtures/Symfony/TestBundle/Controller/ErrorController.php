<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class ErrorController
{
    /**
     * @Route(
     *     methods={"GET"},
     *     path="/error"
     * )
     */
    public function error(): JsonResponse
    {
        $callback = function (int $a) {
            $a++;
        };
        $callback("abc"); // it is expected to get a TypeError, so we can test the production exception handler

        return new JsonResponse(['hello' => 'error!'], 500);
    }
}
