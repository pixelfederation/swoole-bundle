<?php

namespace K911\Swoole\Server\Runtime\HMR;

use Iterator;

/**
 *
 */
interface LoadedFilesInterface extends Iterator
{
    /**
     * @param string $file
     *
     * @return void
     */
    public function addFile(string $file): void;

    /**
     * @return array
     */
    public function toArray(): array;
}
