<?php

declare(strict_types=1);

/*
 * @author Martin Fris <rasta@lj.sk>
 */

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use K911\Swoole\Bridge\Symfony\RequestCycle\TerminatorInterface;

/**
 *
 */
final class EntityManagersHandler implements TerminatorInterface
{
    /**
     * @var EntityManagerInterface[]|ObjectManager[]
     */
    private $entityManagers;

    /**
     * @param array $entityManagers[]
     */
    public function __construct(array $entityManagers)
    {
        $this->entityManagers = $entityManagers;
    }

    /**
     * @return void
     */
    public function terminate(): void
    {
        /* @var $entityManager ResettableEntityManager */
        foreach ($this->entityManagers as $entityManager) {
            $entityManager->clearOrResetIfNeeded();
        }
    }
}
