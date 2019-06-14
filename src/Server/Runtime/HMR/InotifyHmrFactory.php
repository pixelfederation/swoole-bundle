<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Server\Runtime\HMR;

use Assert\AssertionFailedException;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Debug\DebugClassLoader;

/**
 *
 */
final class InotifyHmrFactory
{
    /**
     * @var LoadedFiles
     */
    private $files;

    /**
     * @param LoadedFiles $files
     */
    public function __construct(LoadedFiles $files)
    {
        $this->files = $files;
    }

    /**
     * @return InotifyHMR
     * @throws AssertionFailedException
     */
    public function getInstance(): InotifyHMR
    {
        // it is a quick way to check if loader was enabled
        $wasDebugEnabled = class_exists(DebugClassLoader::class, false);
        if ($wasDebugEnabled) {
            // disable temporary to apply AOP loader first
            DebugClassLoader::disable();
        }

        $this->initHmrComposerLoaders();

        if ($wasDebugEnabled) {
            DebugClassLoader::enable();
        }

        return new InotifyHMR($this->files);
    }

    /**
     * @return void
     */
    private function initHmrComposerLoaders(): void
    {
        $loaders = spl_autoload_functions();

        foreach ($loaders as &$loader) {
            $loaderToUnregister = $loader;
            if (is_array($loader) && ($loader[0] instanceof ClassLoader)) {
                $loader[0] = new HmrComposerLoader($this->files, $loader[0]);
            }
            spl_autoload_unregister($loaderToUnregister);
        }
        unset($loader);

        foreach ($loaders as $loader) {
            spl_autoload_register($loader);
        }
    }
}
