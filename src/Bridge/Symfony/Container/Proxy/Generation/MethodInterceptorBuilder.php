<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation;

use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\MethodGenerator\InterceptedMethod;
use K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\PropertyGenerator\ServicePoolHolderProperty;
use Laminas\Code\Reflection\MethodReflection;
use ReflectionMethod;

final class MethodInterceptorBuilder
{
    public function buildMethodInterceptor(ServicePoolHolderProperty $containerHolder): callable
    {
        return static function (ReflectionMethod $method) use ($containerHolder): InterceptedMethod {
            return InterceptedMethod::generateMethod(
                new MethodReflection($method->getDeclaringClass()->getName(), $method->getName()),
                $containerHolder
            );
        };
    }
}
