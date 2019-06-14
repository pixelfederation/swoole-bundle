<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\GoAop\AspectSymfonyKernel;
use K911\Swoole\Server\Runtime\HMR\InotifyHMR;
use K911\Swoole\Server\Runtime\HMR\LoadedFiles;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class GoAopAspectKernelOverridePass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // don't run if GoAop isn't enabled
        if (!$container->hasParameter('goaop.options')) {
            return;
        }

        $aspectKernelDef = $container->findDefinition('goaop.aspect.kernel');
        $aspectKernelDef->setFactory(sprintf('%s::getInstanceForHmr', AspectSymfonyKernel::class));
        $aspectKernelDef->setArgument('$files', new Reference(LoadedFiles::class));

        // disable custom autoloader override
        $hmrDef = $container->findDefinition(InotifyHMR::class);
        $hmrDef->setFactory(null);
        $hmrDef->setArgument('$loadedFiles', new Reference(LoadedFiles::class));
    }
}
