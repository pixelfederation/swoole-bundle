<?php
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Tests\Unit\Bridge\Doctrine\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use K911\Swoole\Bridge\Doctrine\ORM\ResettableEntityManager;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Entity\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 *
 */
class ResettableEntityManagerTest extends TestCase
{
    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return void
     * @dataProvider getDataSet
     */
    public function testMethod(string $method, $result = null, array $arguments = []): void
    {
        $em = $this->getResettableEntityManager($method, $result, $arguments);
        $actual = null;

        if ($arguments) {
            $actual = $em->{$method}(...$arguments);
        } else {
            $actual = $em->{$method}();
        }

        self::assertSame($result, $actual);
    }

    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return void
     * @dataProvider getDataSet
     */
    public function testMethodThrows(string $method, $result = null, array $arguments = []): void
    {
        $this->expectException(ORMException::class);
        $em = $this->getThrowingResettableEntityManager($method, $result, $arguments);
        $actual = null;

        if ($arguments) {
            $actual = $em->{$method}(...$arguments);
        } else {
            $actual = $em->{$method}();
        }

        self::assertSame($result, $actual);
    }

    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return void
     * @dataProvider getDataSet
     */
    public function testMethodEmIsResettedAfterThrow(string $method, $result = null, array $arguments = []): void
    {
        $em = $this->getThrowingResettableEntityManager($method, $result, $arguments);

        try {
            if ($arguments) {
                $em->{$method}(...$arguments);
            } else {
                $em->{$method}();
            }
        } catch (ORMException $e) {
            self::assertTrue(true);
        }

        $actual = null;

        if ($arguments) {
            $actual = $em->{$method}(...$arguments);
        } else {
            $actual = $em->{$method}();
        }

        self::assertSame($result, $actual);
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getDataSet(): array
    {
        return [
            ['getCache', $this->createMock(Cache::class)],
            ['getConnection', $this->createMock(Connection::class)],
            ['getExpressionBuilder', $this->createMock(Expr::class)],
            ['beginTransaction'],
            ['transactional', null, [static function() {}]],
            ['commit'],
            ['rollback'],
            ['createQuery', $this->createMock(AbstractQuery::class)],
            ['createNamedQuery', $this->createMock(AbstractQuery::class), ['name']],
            [
                'createNativeQuery',
                $this->createMock(AbstractQuery::class),
                ['sql', $this->createMock(Query\ResultSetMapping::class)],
            ],
            ['createNamedNativeQuery', $this->createMock(AbstractQuery::class), ['query']],
            ['createQueryBuilder', $this->createMock(QueryBuilder::class)],
            ['getReference', $this->createMock(Test::class), [Test::class, 1]],
            ['getPartialReference', $this->createMock(Test::class), [Test::class, 1]],
            ['close'],
            ['copy', $this->createMock(Test::class), [$this->createMock(Test::class), true]],
            ['lock', null, [$this->createMock(Test::class), LockMode::OPTIMISTIC, 1]],
            ['getEventManager', $this->createMock(EventManager::class)],
            ['getConfiguration', $this->createMock(Configuration::class)],
            ['isOpen', true],
            ['getUnitOfWork', $this->createMock(UnitOfWork::class)],
            ['getHydrator', $this->createMock(AbstractHydrator::class), [Query::HYDRATE_OBJECT]],
            ['newHydrator', $this->createMock(AbstractHydrator::class), [Query::HYDRATE_OBJECT]],
            ['getProxyFactory', $this->createMock(ProxyFactory::class)],
            ['getFilters', $this->createMock(Query\FilterCollection::class)],
            ['isFiltersStateClean', true],
            ['hasFilters', true],
            ['find', $this->createMock(Test::class), [Test::class, 1]],
            ['persist', null, [$this->createMock(Test::class)]],
            ['remove', null, [$this->createMock(Test::class)]],
            ['merge', null, [$this->createMock(Test::class)]],
            ['clear', null, [Test::class]],
            ['detach', null, [$this->createMock(Test::class)]],
            ['refresh', null, [$this->createMock(Test::class)]],
            ['flush'],
            ['getRepository', $this->createMock(ObjectRepository::class), [Test::class]],
            ['getClassMetadata', $this->createMock(ClassMetadata::class), [Test::class]],
            ['getMetadataFactory', $this->createMock(ClassMetadataFactory::class)],
            ['initializeObject', null, [$this->createMock(ArrayCollection::class)]],
            ['contains', true, [$this->createMock(Test::class)]],
        ];
    }

    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return ResettableEntityManager
     */
    private function getResettableEntityManager(string $method, $result, array $arguments = []): ResettableEntityManager
    {
        return new ResettableEntityManager(
            $this->getEmMockForMethod($method, $result, $arguments),
            $this->getDoctrineRegistryMock(),
            'default'
        );
    }

    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return ResettableEntityManager
     */
    private function getThrowingResettableEntityManager(string $method, $result, array $arguments = []): ResettableEntityManager
    {
        return new ResettableEntityManager(
            $this->getThrowingEmMockForMethod($method, $arguments),
            $this->getResettingDoctrineRegistryMock($this->getEmMockForMethod($method, $result, $arguments), 'default'),
            'default'
        );
    }

    /**
     * @param string $method
     * @param        $result
     * @param array  $arguments
     *
     * @return EntityManagerInterface
     */
    private function getEmMockForMethod(string $method, $result, array $arguments = []): EntityManagerInterface
    {
        /* @var $emMock EntityManagerInterface|ObjectProphecy */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $args = empty($arguments) ? [] : $this->prophecyArguments($arguments);
        $emMock->{$method}(...$args)->willReturn($result);

        return $emMock->reveal();
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return ResettableEntityManager
     */
    private function getThrowingEmMockForMethod(string $method, array $arguments = []): EntityManagerInterface
    {
        /* @var $emMock EntityManagerInterface|ObjectProphecy */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $args = empty($arguments) ? [] : $this->prophecyArguments($arguments);
        $emMock->{$method}(...$args)->shouldBeCalledTimes(1)->willThrow(new ORMException('test'));

        return $emMock->reveal();
    }

    /**
     * @return RegistryInterface
     */
    private function getDoctrineRegistryMock(): RegistryInterface
    {
        /* @var $registry RegistryInterface|ObjectProphecy */
        $registry = $this->prophesize(RegistryInterface::class);

        return $registry->reveal();
    }

    /**
     * @param EntityManagerInterface $resetToEm
     *
     * @return RegistryInterface
     */
    private function getResettingDoctrineRegistryMock(EntityManagerInterface $resetToEm, string $emName): RegistryInterface
    {
        /* @var $registry RegistryInterface|ObjectProphecy */
        $registry = $this->prophesize(RegistryInterface::class);
        $registry->resetManager(Argument::exact($emName))->shouldBeCalledTimes(1)->willReturn($resetToEm);

        return $registry->reveal();
    }

    /**
     * @param array $arguments
     *
     * @return array
     */
    private function prophecyArguments(array $arguments): array
    {
        return array_map(static function($argument) {
            return Argument::exact($argument);
        }, $arguments);
    }
}
