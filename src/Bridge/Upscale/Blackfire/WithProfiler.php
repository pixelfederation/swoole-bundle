<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Upscale\Blackfire;

use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use Swoole\Http\Server;

final class WithProfiler implements ConfiguratorInterface
{
    public function __construct(private ProfilerActivator $profilerActivator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->profilerActivator->activate($server);
    }
}
