<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation;

use K911\Swoole\Bridge\Symfony\Container\ServicePool;
use OutOfBoundsException;
use ProxyManager\Configuration;
use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ProxyManager\Signature\Exception\InvalidSignatureException;
use ProxyManager\Signature\Exception\MissingSignatureException;

/**
 * Factory responsible of producing proxy objects.
 */
class AccessInterceptorServicePoolFactory extends AbstractBaseFactory
{
    private $generator;

    public function __construct(?Configuration $configuration = null)
    {
        parent::__construct($configuration);

        $this->generator = new AccessInterceptorServicePoolGenerator(new MethodInterceptorBuilder());
    }

    /**
     * @template RealObjectType of object
     *
     * @param class-string<RealObjectType> $serviceClass
     *
     * @throws InvalidSignatureException
     * @throws MissingSignatureException
     * @throws OutOfBoundsException
     *
     * @return RealObjectType
     */
    public function createProxy(ServicePool $servicePool, string $serviceClass)
    {
        $proxyClassName = $this->generateProxy($serviceClass);

        return new $proxyClassName($servicePool);
    }

    protected function getGenerator(): ProxyGeneratorInterface
    {
        return $this->generator;
    }
}
