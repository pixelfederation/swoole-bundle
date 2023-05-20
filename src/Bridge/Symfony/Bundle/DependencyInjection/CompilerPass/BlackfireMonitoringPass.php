<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\ContainerConstants;
use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Upscale\Blackfire\Monitoring\Apm;
use K911\Swoole\Bridge\Upscale\Blackfire\Monitoring\BlackfireMiddlewareFactory;
use K911\Swoole\Bridge\Upscale\Blackfire\Monitoring\RequestMonitoring;
use K911\Swoole\Bridge\Upscale\Blackfire\Monitoring\WithApm;
use K911\Swoole\Server\Middleware\MiddlewareInjector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BlackfireMonitoringPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $enabled = $container->getParameter(ContainerConstants::PARAM_BLACKFIRE_MONITORING_ENABLED);

        if (true !== $enabled) {
            return;
        }

        $container->register(RequestMonitoring::class)
            ->setClass(RequestMonitoring::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$requestFactory', new Reference(RequestFactoryInterface::class))
        ;

        $container->register(BlackfireMiddlewareFactory::class)
            ->setClass(BlackfireMiddlewareFactory::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$monitoring', new Reference(RequestMonitoring::class))
        ;

        $container->register(Apm::class)
            ->setClass(Apm::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$injector', new Reference(MiddlewareInjector::class))
            ->setArgument('$middlewareFactory', new Reference(BlackfireMiddlewareFactory::class))
        ;

        $container->register(WithApm::class)
            ->setClass(WithApm::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$apm', new Reference(Apm::class))
        ;
        $def = $container->getDefinition('swoole_bundle.server.http_server.configurator.for_server_run_command');
        $def->addArgument(new Reference(WithApm::class));
        $def = $container->getDefinition('swoole_bundle.server.http_server.configurator.for_server_start_command');
        $def->addArgument(new Reference(WithApm::class));
    }
}
