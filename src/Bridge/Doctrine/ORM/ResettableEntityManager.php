<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 *
 */
class ResettableEntityManager extends EntityManagerDecorator
{
    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var RegistryInterface
     */
    private $doctrineRegistry;

    /**
     * @var string
     */
    private $decoratedName;

    /**
     * @param Configuration          $configuration
     * @param EntityManagerInterface $wrapped
     * @param RegistryInterface      $doctrineRegistry
     * @param string                 $decoratedName
     */
    public function __construct(
        Configuration $configuration,
        EntityManagerInterface $wrapped,
        RegistryInterface $doctrineRegistry,
        string $decoratedName
    ) {
        $this->repositoryFactory = $configuration->getRepositoryFactory();
        $this->doctrineRegistry = $doctrineRegistry;
        $this->decoratedName = $decoratedName;
        parent::__construct($wrapped);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * @return void
     */
    public function clearOrResetIfNeeded(): void
    {
        if ($this->wrapped->isOpen()) {
            $this->clear();

            return;
        }

        $this->wrapped = $this->doctrineRegistry->resetManager($this->decoratedName);
    }
}
