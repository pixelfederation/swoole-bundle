<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Symfony\RequestCycle\InitializerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 *
 */
final class EntityManagersHandler implements InitializerInterface, ResetInterface
{
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var EntityManagerInterface[]|ObjectManager[]
     */
    private $entityManagers;

    /**
     * @param Registry $doctrineRegistry
     */
    public function __construct(Registry $doctrineRegistry)
    {
        $this->entityManagers = $doctrineRegistry->getManagers();
        $this->connections = array_map(static function (EntityManagerInterface $entityManager) {
            return $entityManager->getConnection();
        }, $this->entityManagers);
    }

    /**
     *
     */
    public function initialize(): void
    {
        foreach ($this->connections as $connection) {
            if ($connection->ping()) {
                continue;
            }

            $connection->close();
            $connection->connect();
        }
    }

    /**
     *
     */
    public function reset(): void
    {
        foreach ($this->entityManagers as $entityManager) {
            $entityManager->clear();
        }
    }
}
