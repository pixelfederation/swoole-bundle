<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle;

use K911\Swoole\Server\Runtime\HMR\HmrComposerLoader;
use K911\Swoole\Server\Runtime\HMR\LoadedFiles;
use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SwooleBundle extends Bundle
{
    /**
     *
     */
    public function boot()
    {
        // it is a quick way to check if loader was enabled
        $isDebugEnabled = $this->container->getParameter('kernel.debug');

        if (!$isDebugEnabled) {
            return;
        }

        $isSwooleEnabled = $this->container->getParameter('swoole_bundle.enabled');
        $isHmrEnabled = $this->container->getParameter('swoole_bundle.hmr_enabled');

        if (!$isSwooleEnabled || !$isHmrEnabled) {
            return;
        }

        // disable temporary to apply HMR loader first
        DebugClassLoader::disable();
        $this->initHmrComposerLoaders();
        DebugClassLoader::enable();
    }

    /**
     * @return void
     */
    private function initHmrComposerLoaders(): void
    {
        $loaders = spl_autoload_functions();

        $files = $this->container->get(LoadedFiles::class);

        foreach ($loaders as &$loader) {
            $loaderToUnregister = $loader;
            if (is_array($loader) && $loader[0]) {
                $loader[0] = new HmrComposerLoader($files, $loader[0], $loader[1]);
                $loader[1] = 'loadClass';
            }
            spl_autoload_unregister($loaderToUnregister);
        }
        unset($loader);

        foreach ($loaders as $loader) {
            spl_autoload_register($loader);
        }
    }
}
