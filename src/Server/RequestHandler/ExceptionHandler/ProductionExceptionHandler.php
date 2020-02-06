<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler\ExceptionHandler;

use ErrorException;
use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use ReflectionClass;
use ReflectionException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Throwable;

final class ProductionExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @var Callable
     */
    private $exceptionHandler;

    /**
     * @param HttpKernelInterface        $kernel
     * @param RequestFactoryInterface    $requestFactory
     * @param ResponseProcessorInterface $responseProcessor
     */
    public function __construct(
        HttpKernelInterface $kernel,
        RequestFactoryInterface $requestFactory,
        ResponseProcessorInterface $responseProcessor
    ) {
        $this->kernel = $kernel;
        $this->requestFactory = $requestFactory;
        $this->responseProcessor = $responseProcessor;
        $this->errorHandler = new ErrorHandler();
    }

    /**
     * @param Request   $request
     * @param Throwable $exception
     * @param Response  $response
     *
     * @throws Throwable
     * @throws ErrorException
     */
    public function handle(Request $request, Throwable $exception, Response $response): void
    {
        $httpFoundationRequest = $this->requestFactory->make($request);
        $this->errorHandler->setExceptionHandler($this->getExceptionHandler());
        $httpFoundationResponse = $this->errorHandler->handleException($exception);
        $this->responseProcessor->process($httpFoundationResponse, $response);

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
        }
    }

    /**
     * @return Callable
     * @throws ReflectionException
     */
    private function getExceptionHandler(): Callable
    {
        if ($this->exceptionHandler !== null) {
            return $this->exceptionHandler;
        }

        $kernel = $this->kernel;
        $reflClass = new ReflectionClass(get_class($kernel));
        $reflMethod = $reflClass->getMethod('handleThrowable');
        $reflMethod->setAccessible(true);
        $reflProperty = $reflClass->getProperty('requestStack');
        $reflProperty->setAccessible(true);
        $requestStack = $reflProperty->getValue($kernel);

        return $this->exceptionHandler = static function () use ($kernel, $requestStack, $reflMethod) {
            $args = func_get_args();
            $args[] = $requestStack->getMasterRequest();
            $args[] = HttpKernelInterface::MASTER_REQUEST;

            return $reflMethod->invokeArgs($kernel, $args);
        };
    }
}
