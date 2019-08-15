<?php
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;
use Exception;
use K911\Swoole\Bridge\Doctrine\ORM\ResettableEntityManager;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Entity\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 *
 */
class ResettableEntityManagerTest extends TestCase
{
    /**
     * @return void
     * @throws Exception
     */
    public function testGetRepository(): void
    {
        /* @var $repositoryFactoryMock RepositoryFactory|ObjectProphecy */
        $repositoryFactoryMock = $this->prophesize(RepositoryFactory::class);
        $repositoryFactoryMock->getRepository(Argument::type(ResettableEntityManager::class), Argument::cetera())
            ->shouldBeCalledTimes(1);
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        $configurationMock->getRepositoryFactory()
            ->shouldBeCalledTimes(1)
            ->willReturn($repositoryFactoryMock->reveal());
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class)->reveal();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class)->reveal();
        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock,
            $registryMock,
            'default'
        );
        /* @var $repository EntityRepository */
        $em->getRepository(Test::class);
    }

    /**
     * @return void
     */
    public function testClearOrResetIfNeededShouldClearWhenWrappedIsOpen(): void
    {
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $emMock->isOpen()->willReturn(true);
        $emMock->clear(Argument::is(null))->shouldBeCalled();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class)->reveal();

        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock->reveal(),
            $registryMock,
            'default'
        );

        $em->clearOrResetIfNeeded();
    }

    /**
     * @return void
     */
    public function testClearOrResetIfNeededShouldResetWhenWrappedIsClosed(): void
    {
        $decoratedName = 'default';
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class)->reveal();
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $emMock->isOpen()->willReturn(false);
        $emMock = $emMock->reveal();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class);
        $registryMock->resetManager(Argument::is($decoratedName))->shouldBeCalled();
        $registryMock = $registryMock->reveal();

        $em = new ResettableEntityManager(
            $configurationMock,
            $emMock,
            $registryMock,
            $decoratedName
        );

        $em->clearOrResetIfNeeded();
    }
}
