<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Doctrine\DBAL;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Symfony\RequestCycle\InitializerInterface;

/**
 *
 */
final class ConnectionsHandler implements InitializerInterface
{
    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @param Registry $doctrineRegistry
     */
    public function __construct(Registry $doctrineRegistry)
    {
        $this->connections = array_map(static function (EntityManagerInterface $entityManager) {
            return $entityManager->getConnection();
        }, $doctrineRegistry->getManagers());
    }

    /**
     * @return void
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
}
