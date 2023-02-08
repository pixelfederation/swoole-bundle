<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Upscale\Blackfire;

use Swoole\Http\Server;
use Upscale\Swoole\Blackfire\Profiler;
use Upscale\Swoole\Blackfire\ProfilerDecorator;
use Upscale\Swoole\Reflection\Http\Server as UpscaleServer;

class ProfilerActivator
{
    public function __construct(private Profiler $profiler)
    {
    }

    public function activate(Server $server): void
    {
        $server = new UpscaleServer($server);
        $server->setMiddleware($this->wrap($server->getMiddleware(), $this->profiler));
    }

    /**
     * Decorate a given middleware for profiling.
     */
    private function wrap(callable $middleware, Profiler $profiler): ProfilerDecorator
    {
        return new ProfilerDecorator($middleware, $profiler);
    }
}
