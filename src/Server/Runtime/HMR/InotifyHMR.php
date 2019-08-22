<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime\HMR;

use Assert\Assertion;
use Assert\AssertionFailedException;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Server;

final class InotifyHMR implements HotModuleReloaderInterface, BootableInterface
{
    /**
     * @var LoadedFilesInterface
     */
    private $composerFiles;

    /**
     * @var array file path => true map
     */
    private $nonReloadableFiles;

    /**
     * @var array file path => true map
     */
    private $watchedFiles;

    /**
     * @var resource returned by \inotify_init
     */
    private $inotify;

    /**
     * @var int \IN_ATRIB
     */
    private $watchMask;

    /**
     * @param LoadedFilesInterface $loadedFiles
     * @param array       $nonReloadableFiles
     *
     * @throws AssertionFailedException
     */
    public function __construct(LoadedFilesInterface $loadedFiles, array $nonReloadableFiles = [])
    {
        $this->composerFiles = $loadedFiles;
        Assertion::extensionLoaded('inotify', 'Swoole HMR requires "inotify" PHP Extension present and loaded in the system.');
        $this->watchMask = defined('IN_ATTRIB') ? (int) constant('IN_ATTRIB') : 4;

        $this->setNonReloadableFiles($nonReloadableFiles);
    }

    public function __destruct()
    {
        if (null !== $this->inotify) {
            fclose($this->inotify);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Server $server
     */
    public function tick(Server $server): void
    {
        $this->watchFiles();
        $events = inotify_read($this->inotify);

        if (false !== $events) {
            $server->reload();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array $runtimeConfiguration
     *
     * @throws AssertionFailedException
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (!empty($runtimeConfiguration['nonReloadableFiles'])) {
            $this->setNonReloadableFiles($runtimeConfiguration['nonReloadableFiles']);
        }

        // Files included before server start cannot be reloaded due to PHP limitations
        $this->setNonReloadableFiles(get_included_files());
        $this->initializeInotify();
    }

    public function getNonReloadableFiles(): array
    {
        return array_keys($this->nonReloadableFiles);
    }

    /**
     * @param string[] $nonReloadableFiles files
     *
     * @throws AssertionFailedException
     */
    private function setNonReloadableFiles(array $nonReloadableFiles): void
    {
        foreach ($nonReloadableFiles as $nonReloadableFile) {
            Assertion::file($nonReloadableFile);
            $this->nonReloadableFiles[$nonReloadableFile] = true;
        }
    }

    private function watchFiles(): void
    {
        $allLoadedFiles = array_merge($this->composerFiles->toArray(), get_included_files());

        foreach ($allLoadedFiles as $file) {
            if (!isset($this->nonReloadableFiles[$file]) && !isset($this->watchedFiles[$file])) {
                $this->watchedFiles[$file] = inotify_add_watch($this->inotify, $file, $this->watchMask);
            }
        }

        $this->composerFiles->clear();
    }

    private function initializeInotify(): void
    {
        $this->inotify = inotify_init();
        stream_set_blocking($this->inotify, false);
    }
}
