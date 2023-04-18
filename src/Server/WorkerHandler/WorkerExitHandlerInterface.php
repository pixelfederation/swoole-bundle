<?php

declare(strict_types=1);

namespace K911\Swoole\Server\WorkerHandler;

use Swoole\Server;

interface WorkerExitHandlerInterface
{
    /**
     * Handle onWorkerStop event.
     * Info: Function will be executed in worker process.
     *
     * To differentiate between server worker and task worker use snippet:
     *
     *      ```php
     *      if($server->taskworker) {
     *        echo "Hello from task worker process";
     *      }
     *      ```
     */
    public function handle(Server $worker, int $workerId): void;
}
