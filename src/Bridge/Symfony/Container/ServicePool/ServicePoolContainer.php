<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\ServicePool;

final class ServicePoolContainer
{
    /**
     * @param array<ServicePool> $pools
     */
    public function __construct(private array $pools)
    {
    }

    public function addPool(ServicePool $pool): void
    {
        $this->pools[] = $pool;
    }

    public function releaseFromCoroutine(int $cId): void
    {
        foreach ($this->pools as $pool) {
            $pool->releaseFromCoroutine($cId);
        }
    }

    public function count(): int
    {
        return count($this->pools);
    }
}
