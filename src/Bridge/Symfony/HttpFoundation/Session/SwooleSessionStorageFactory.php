<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation\Session;

use K911\Swoole\Server\Session\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

final class SwooleSessionStorageFactory implements SessionStorageFactoryInterface
{
    private ?MetadataBag $metadataBag;

    private StorageInterface $storage;

    private int $lifetimeSeconds;

    public function __construct(
        StorageInterface $storage,
        ?MetadataBag $metadataBag = null,
        int $lifetimeSeconds = 86400
    ) {
        $this->storage = $storage;
        $this->metadataBag = $metadataBag;
        $this->lifetimeSeconds = $lifetimeSeconds;
    }

    /**
     * {@inheritDoc}
     */
    public function createStorage(?Request $request): SessionStorageInterface
    {
        return new SwooleSessionStorage(
            $this->storage,
            SwooleSessionStorage::DEFAULT_SESSION_NAME,
            $this->lifetimeSeconds,
            $this->metadataBag
        );
    }
}
