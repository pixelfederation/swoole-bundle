<?php
declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Symfony\Logging\ChannelLogger;
use K911\Swoole\Bridge\Symfony\Logging\MasterLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class LoggerReplacerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('swoole_bundle.enabled')) {
            return;
        }

        $channelLoggerEnabled = $container->getParameter('swoole_bundle.channel_logger');

        if (!$channelLoggerEnabled) {
            return;
        }

        $loggerDef = $container->findDefinition('logger');
        $channelLoggerDef = $container->findDefinition(ChannelLogger::class);

        $container->setDefinition('swoole_bundle.original_logger', $loggerDef);
        $container->setDefinition('logger', $channelLoggerDef);
        $container->setDefinition(LoggerInterface::class, $channelLoggerDef);

        $monologReqLoggerDef = $container->findDefinition('monolog.logger.request');
        $container->setDefinition('swoole_bundle.original_monolog.logger.request', $monologReqLoggerDef);
        $container->setDefinition('monolog.logger.request', $channelLoggerDef);

        $masterLoggerDef = $container->findDefinition(MasterLogger::class);
        $masterLoggerDef->replaceArgument('$logger', new Reference('swoole_bundle.original_logger'));
    }
}
