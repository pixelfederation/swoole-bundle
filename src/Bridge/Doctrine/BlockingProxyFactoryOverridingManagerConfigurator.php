<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Doctrine;

use Doctrine\Bundle\DoctrineBundle\ManagerConfigurator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Component\Locking\FirstTimeOnly\FirstTimeOnlyMutexFactory;

final class BlockingProxyFactoryOverridingManagerConfigurator
{
    private static ?\ReflectionProperty $emProxyFactoryPropRefl = null;

    public function __construct(
        private ManagerConfigurator $wrapped,
        private FirstTimeOnlyMutexFactory $mutexFactory
    ) {
    }

    public function configure(EntityManagerInterface $entityManager): void
    {
        if (!$entityManager instanceof EntityManager) {
            throw new \UnexpectedValueException(sprintf('%s needed, got %s.', EntityManager::class, $entityManager::class));
        }

        $this->replaceProxyFactory($entityManager);
        $this->wrapped->configure($entityManager);
    }

    private function replaceProxyFactory(EntityManager $entityManager): void
    {
        $proxyFactory = new BlockingProxyFactory($entityManager->getProxyFactory(), $this->mutexFactory);
        $proxyFactoryProp = $this->getEmProxyFactoryReflectionProperty();
        $proxyFactoryProp->setValue($entityManager, $proxyFactory);
    }

    private function getEmProxyFactoryReflectionProperty(): \ReflectionProperty
    {
        if (null === self::$emProxyFactoryPropRefl) {
            $emReflClass = new \ReflectionClass(EntityManager::class);
            self::$emProxyFactoryPropRefl = $emReflClass->getProperty('proxyFactory');
            self::$emProxyFactoryPropRefl->setAccessible(true);
        }

        return self::$emProxyFactoryPropRefl;
    }
}
