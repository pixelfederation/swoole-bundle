<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use K911\Swoole\Bridge\Doctrine\ORM\ResettableEntityManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
final class EntityManagerDecoratorPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('swoole_bundle.enabled')) {
            return;
        }

        $entityManagers = $container->getParameter('doctrine.entity_managers');

        foreach ($entityManagers as $name => $id) {
            $emDefinition = $container->findDefinition($id);
            $newId = $id . '_swoole';
            $configArg = $emDefinition->getArgument(1);

            $decoratorDef = new Definition(ResettableEntityManager::class, [
                '$configuration' => $configArg,
                '$decorated' => new Reference($newId),
                '$doctrineRegistry' => new Reference('doctrine'),
                '$decoratedName' => $name,
            ]);

           $container->setDefinition($id, $decoratorDef);
           $container->setDefinition($newId, $emDefinition);

            $entityManagers[$name] = $newId;
        }

        $container->setParameter('doctrine.entity_managers', $entityManagers);
    }
}
