<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;
use K911\Swoole\Bridge\Doctrine\ORM\EntityManagersHandler;
use K911\Swoole\Bridge\Doctrine\ORM\ResettableEntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 *
 */
class EntityManagersHandlerTest extends TestCase
{
    /**
     * @var EntityManagersHandler
     */
    private $emHandler;

    /**
     * @var Registry|ObjectProphecy
     */
    private $doctrineRegistryProphecy;

    /**
     * @var ResettableEntityManager|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->entityManagerProphecy = $this->prophesize(ResettableEntityManager::class);
        $this->doctrineRegistryProphecy = $this->prophesize(Registry::class);

        /** @var Registry $doctrineRegistryMock */
        $doctrineRegistryMock = $this->doctrineRegistryProphecy->reveal();

        $this->setUpRegistryEnityManagers();
        $this->emHandler = new EntityManagersHandler($doctrineRegistryMock);
    }

    /**
     *
     */
    public function testHandleEntityManagerClearingOnAppTerminate(): void
    {
        $this->entityManagerProphecy->clearOrResetIfNeeded()->shouldBeCalled();
        $this->emHandler->terminate();
    }

    /**
     *
     */
    private function setUpRegistryEnityManagers(): void
    {
        $this->doctrineRegistryProphecy->getManagers()->willReturn([$this->entityManagerProphecy->reveal()]);
    }
}
