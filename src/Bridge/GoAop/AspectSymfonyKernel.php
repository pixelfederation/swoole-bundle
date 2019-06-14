<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\GoAop;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use Go\Instrument\ClassLoading\AopComposerLoader;
use K911\Swoole\Server\Runtime\HMR\HmrComposerLoader;
use K911\Swoole\Server\Runtime\HMR\LoadedFiles;
use RuntimeException;
use Symfony\Component\Debug\DebugClassLoader;

/**
 *
 */
final class AspectSymfonyKernel extends AspectKernel
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
        parent::__construct();
    }

    /**
     * @param LoadedFiles $files
     *
     * @return AspectSymfonyKernel
     */
    public static function getInstanceForHmr(LoadedFiles $files): self
    {
        if (self::$instance === null) {
            self::$instance = new self($files);
        }

        return self::$instance;
    }

    /**
     * Configure an AspectContainer with advisors, aspects and pointcuts
     *
     * @param AspectContainer $container
     *
     * @return void
     */
    protected function configureAop(AspectContainer $container)
    {
    }

    /**
     * Cache warmer in SF doesn't call Bundle::boot, so we need to duplicate this logic one more time
     *
     * @inheritDoc
     */
    public function init(array $options = [])
    {
        // it is a quick way to check if loader was enabled
        $wasDebugEnabled = class_exists(DebugClassLoader::class, false);
        if ($wasDebugEnabled) {
            // disable temporary to apply AOP loader first
            DebugClassLoader::disable();
        }
        parent::init($options);
        $this->initHmrComposerLoaders();

        if (!AopComposerLoader::wasInitialized()) {
            throw new RuntimeException('Initialization of AOP loader was failed, probably due to Debug::enable()');
        }
        if ($wasDebugEnabled) {
            DebugClassLoader::enable();
        }
    }

    /**
     * @return void
     */
    private function initHmrComposerLoaders(): void
    {
        $loaders = spl_autoload_functions();

        foreach ($loaders as &$loader) {
            $loaderToUnregister = $loader;
            if (is_array($loader) && ($loader[0] instanceof AopComposerLoader)) {echo 'z';
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
