<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Doctrine\ORM;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 *
 */
class ResettableEntityManager implements EntityManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $decorated;

    /**
     * @var RegistryInterface
     */
    private $doctrineRegistry;

    /**
     * @var string
     */
    private $decoratedName;

    /**
     * @param EntityManagerInterface $decorated
     * @param RegistryInterface      $doctrineRegistry
     * @param string                 $decoratedName
     */
    public function __construct(
        EntityManagerInterface $decorated,
        RegistryInterface $doctrineRegistry,
        string $decoratedName
    ) {
        $this->decorated = $decorated;
        $this->doctrineRegistry = $doctrineRegistry;
        $this->decoratedName = $decoratedName;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCache()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getConnection()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getExpressionBuilder()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function beginTransaction()
    {
        $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function transactional($func)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function commit()
    {
        $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function rollback()
    {
        $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createQuery($dql = '')
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createNamedQuery($name)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createNamedNativeQuery($name)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createQueryBuilder()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getReference($entityName, $id)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getPartialReference($entityName, $identifier)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function close()
    {
        $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function copy($entity, $deep = false)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function lock($entity, $lockMode, $lockVersion = null)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getEventManager()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getConfiguration()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function isOpen()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getUnitOfWork()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getHydrator($hydrationMode)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function newHydrator($hydrationMode)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getProxyFactory()
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getFilters()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function isFiltersStateClean()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function hasFilters()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function find($className, $id)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function persist($object)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function remove($object)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function merge($object)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function clear($objectName = null)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function detach($object)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function refresh($object)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function flush()
    {
        $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getRepository($className)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getClassMetadata($className)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMetadataFactory()
    {
        return $this->wrapDecoratedCall(__FUNCTION__);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function initializeObject($obj)
    {
        $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function contains($object)
    {
        return $this->wrapDecoratedCall(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $methodName
     *
     * @param array  $arguments
     *
     * @return mixed
     * @throws Exception
     */
    private function wrapDecoratedCall(string $methodName, array $arguments = [])
    {
        try {
            return $this->decorated->{$methodName}(...$arguments);
        } catch (ORMException | DBALException $e) {
            $this->decorated = $this->doctrineRegistry->resetManager($this->decoratedName);
            throw $e;
        }
    }
}
