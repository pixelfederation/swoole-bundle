<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\ErrorHandler;

use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponder
{
    public function __construct(
        private ErrorHandler $errorHandler,
        private ExceptionHandlerFactory $handlerFactory
    ) {
    }

    public function processErroredRequest(Request $request, \Throwable $throwable): Response
    {
        $exceptionHandler = $this->handlerFactory->newExceptionHandler($request);
        $this->errorHandler->setExceptionHandler($exceptionHandler);
        $this->errorHandler->handleException($throwable);

        return $exceptionHandler->getResponse();
    }
}
