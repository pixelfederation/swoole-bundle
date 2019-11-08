<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\DependencyInjection;

use Exception;
use K911\Swoole\Bridge\Symfony\HttpFoundation\CloudFrontRequestFactory;
use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use K911\Swoole\Bridge\Symfony\HttpFoundation\Session\SetSessionCookieEventListener;
use K911\Swoole\Bridge\Symfony\HttpFoundation\TrustAllProxiesRequestHandler;
use K911\Swoole\Bridge\Symfony\HttpKernel\DebugHttpKernelRequestHandler;
use K911\Swoole\Bridge\Symfony\Profiling\BlackfireHandler;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskTransportFactory;
use K911\Swoole\Bridge\Symfony\Messenger\SwooleServerTaskTransportHandler;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Config\Sockets;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\RequestHandler\AdvancedStaticFilesServer;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\ExceptionHandlerInterface;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\JsonExceptionHandler;
use K911\Swoole\Server\RequestHandler\ExceptionHandler\ProductionExceptionHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use K911\Swoole\Server\Runtime\HMR\HotModuleReloaderInterface;
use K911\Swoole\Server\Runtime\HMR\InotifyHMR;
use K911\Swoole\Server\Runtime\HMR\LoadedFiles;
use K911\Swoole\Server\TaskHandler\TaskHandlerInterface;
use K911\Swoole\Server\WorkerHandler\HMRWorkerStartHandler;
use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class SwooleExtension
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SwooleExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $mode                = $mergedConfig['mode'];
        $swooleBundleEnabled = $mode === 'strict' || extension_loaded('swoole');

        $container->setParameter('swoole_bundle.enabled', $swooleBundleEnabled);
        $container->setParameter('swoole_bundle.hmr_enabled', false);

        // this is a hack to fulfill Symfony container conditions to use every env variable used in the config
        $this->registerHttpServerParameters($mergedConfig['http_server'], $container);

        if (!$swooleBundleEnabled) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('commands.yaml');

        $container->registerForAutoconfiguration(BootableInterface::class)
            ->addTag('swoole_bundle.bootable_service')
        ;
        $container->registerForAutoconfiguration(ConfiguratorInterface::class)
            ->addTag('swoole_bundle.server_configurator');

        $this->registerHttpServer($mergedConfig['http_server'], $container);

        if ($mergedConfig['messenger_workers']['enabled'] && interface_exists(TransportFactoryInterface::class)) {
            $this->registerSwooleServerTransportConfiguration($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'swoole';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return Configuration::fromTreeBuilder();
    }

    /**
     * @param array            $httpServerConfig
     * @param ContainerBuilder $container
     */
    private function registerHttpServerParameters(array $httpServerConfig, ContainerBuilder $container): void
    {
        $container->setParameter('swoole.http_server.host', $httpServerConfig['host']);
        $container->setParameter('swoole.http_server.port', $httpServerConfig['port']);
        $container->setParameter('swoole.http_server.trusted_proxies', $httpServerConfig['trusted_proxies']);
        $container->setParameter('swoole.http_server.trusted_hosts', $httpServerConfig['trusted_hosts']);
        $container->setParameter('swoole.http_server.api.host', $httpServerConfig['api']['host']);
        $container->setParameter('swoole.http_server.api.port', $httpServerConfig['api']['port']);
        $container->setParameter('swoole.http_server.settings.reactor_count', $httpServerConfig['settings']['reactor_count']);
        $container->setParameter('swoole.http_server.settings.worker_count', $httpServerConfig['settings']['worker_count']);
    }

    /**
     * @param array            $httpServerConfig
     * @param ContainerBuilder $container
     *
     * @throws ServiceNotFoundException
     */
    private function registerHttpServer(array $httpServerConfig, ContainerBuilder $container): void
    {
        $container->setParameter('swoole.http_server.trusted_proxies', $httpServerConfig['trusted_proxies']);
        $container->setParameter('swoole.http_server.trusted_hosts', $httpServerConfig['trusted_hosts']);
        $container->setParameter('swoole.http_server.api.host', $httpServerConfig['api']['host']);
        $container->setParameter('swoole.http_server.api.port', $httpServerConfig['api']['port']);

        $this->registerHttpServerServices($httpServerConfig, $container);
        $this->registerExceptionHandler($httpServerConfig['exception_handler'], $container);
        $this->registerHttpServerConfiguration($httpServerConfig, $container);
    }

    private function registerExceptionHandler(array $config, ContainerBuilder $container): void
    {
        [
            'handler_id' => $handlerId,
            'type' => $type,
            'verbosity' => $verbosity,
        ] = $config;

        if ('auto' === $type) {
            $type = $this->isProd($container) ? 'production' : 'json';
        }

        switch ($type) {
            case 'json':
                $class = JsonExceptionHandler::class;

                break;
            case 'custom':
                $class = $handlerId;

                break;
            default: // case 'production'
                $class = ProductionExceptionHandler::class;

                break;
        }

        $container->setAlias(ExceptionHandlerInterface::class, $class);

        if ('auto' === $verbosity) {
            if ($this->isProd($container)) {
                $verbosity = 'production';
            } elseif ($this->isDebug($container)) {
                $verbosity = 'trace';
            } else {
                $verbosity = 'verbose';
            }
        }

        $container->getDefinition(JsonExceptionHandler::class)
            ->setArgument('$verbosity', $verbosity)
        ;
    }

    private function registerSwooleServerTransportConfiguration(ContainerBuilder $container): void
    {
        $container->register(SwooleServerTaskTransportFactory::class)
            ->addTag('messenger.transport_factory')
            ->addArgument(new Reference(HttpServer::class))
        ;

        $container->register(SwooleServerTaskTransportHandler::class)
            ->addArgument(new Reference(MessageBusInterface::class))
            ->addArgument(new Reference(SwooleServerTaskTransportHandler::class.'.inner'))
            ->setDecoratedService(TaskHandlerInterface::class, null, -10)
        ;
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function registerHttpServerConfiguration(array $config, ContainerBuilder $container): void
    {
        [
            'static' => $static,
            'api' => $api,
            'hmr' => $hmr,
            'host' => $host,
            'port' => $port,
            'running_mode' => $runningMode,
            'socket_type' => $socketType,
            'ssl_enabled' => $sslEnabled,
            'settings' => $settings,
        ] = $config;

        if ('auto' === $static['strategy']) {
            $static['strategy'] = $this->isDebugOrNotProd($container) ? 'advanced' : 'off';
        }

        if ('advanced' === $static['strategy']) {
            $container->register(AdvancedStaticFilesServer::class)
                ->setArgument('$decorated', new Reference(AdvancedStaticFilesServer::class.'.inner'))
                ->setArgument('$configuration', new Reference(HttpServerConfiguration::class))
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -60)
                ->addTag('swoole_bundle.bootable_service')
            ;
        }

        $settings['serve_static'] = $static['strategy'];
        $settings['public_dir'] = $static['public_dir'];

        if ('auto' === $settings['log_level']) {
            $settings['log_level'] = $this->isDebug($container) ? 'debug' : 'notice';
        }

        if ('auto' === $hmr) {
            $hmr = $this->resolveAutoHMR();
        }

        $sockets = $container->getDefinition(Sockets::class)
            ->addArgument(new Definition(Socket::class, [$host, $port, $socketType, $sslEnabled]))
        ;

        if ($api['enabled']) {
            $sockets->addArgument(new Definition(Socket::class, [$api['host'], $api['port']]));
        }

        $container->getDefinition(HttpServerConfiguration::class)
            ->replaceArgument('$sockets', new Reference(Sockets::class))
            ->replaceArgument('$runningMode', $runningMode)
            ->replaceArgument('$settings', $settings);

        $hmrEnabled = $this->registerHttpServerHMR($hmr, $container);
        $container->setParameter('swoole_bundle.hmr_enabled', $hmrEnabled);
    }

    /**
     * @param string           $hmr
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function registerHttpServerHMR(string $hmr, ContainerBuilder $container): bool
    {
        if ('off' === $hmr || !$this->isDebug($container)) {
            return false;
        }

        if ('inotify' === $hmr) {
            $container->register(LoadedFiles::class)
                ->setPublic(true);

            $container->register(InotifyHMR::class)
                ->setArgument('$loadedFiles', new Reference(LoadedFiles::class))
                ->setPublic(false)
                ->addTag('swoole_bundle.bootable_service')
            ;

            $container->register(HotModuleReloaderInterface::class, InotifyHMR::class)
                ->setArgument('$loadedFiles', new Reference(LoadedFiles::class))
                ->setPublic(false)
                ->addTag('swoole_bundle.bootable_service')
            ;
        }

        $container->register(HMRWorkerStartHandler::class)
            ->setPublic(false)
            ->setArgument('$hmr', new Reference(HotModuleReloaderInterface::class))
            ->setArgument('$interval', 2000)
            ->setArgument('$decorated', new Reference(HMRWorkerStartHandler::class.'.inner'))
            ->setDecoratedService(WorkerStartHandlerInterface::class)
        ;

        return true;
    }

    /**
     * @return string
     */
    private function resolveAutoHMR(): string
    {
        if (extension_loaded('inotify')) {
            return 'inotify';
        }

        return 'off';
    }

    /**
     * Registers optional http server dependencies providing various features.
     *
     * @param array            $httpServerConfig
     * @param ContainerBuilder $container
     */
    private function registerHttpServerServices(array $httpServerConfig, ContainerBuilder $container): void
    {
        $servicesConfig = $httpServerConfig['services'];

        // RequestFactoryInterface
        // -----------------------
        if ($servicesConfig['cloudfront_proto_header_handler']) {
            $container->register(CloudFrontRequestFactory::class)
                ->addArgument(new Reference(CloudFrontRequestFactory::class.'.inner'))
                ->setPublic(false)
                ->setDecoratedService(RequestFactoryInterface::class, null, -10)
            ;
        }

        // RequestHandlerInterface
        // -------------------------
        if ($servicesConfig['trust_all_proxies_handler']) {
            $container->register(TrustAllProxiesRequestHandler::class)
                ->addArgument(new Reference(TrustAllProxiesRequestHandler::class.'.inner'))
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -10)
                ->addTag('swoole_bundle.bootable_service')
            ;
        }

        if ($servicesConfig['debug_handler'] || (null === $servicesConfig['debug_handler'] && $this->isDebug($container))) {
            $container->register(DebugHttpKernelRequestHandler::class)
                ->setArgument('$decorated', new Reference(DebugHttpKernelRequestHandler::class.'.inner'))
                ->setArgument('$kernel', new Reference('kernel'))
                ->setArgument('$container', new Reference('service_container'))
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -50)
            ;
        }

        if ($servicesConfig['blackfire_handler']) {
            $container->register(BlackfireHandler::class)
                ->setArgument('$decorated', new Reference(BlackfireHandler::class.'.inner'))
                ->setPublic(false)
                ->setDecoratedService(RequestHandlerInterface::class, null, -49);
        }

        if ($servicesConfig['session_cookie_event_listener']) {
            $container->register(SetSessionCookieEventListener::class)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(false)
            ;
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $bundleName
     *
     * @return bool
     */
    private function isBundleLoaded(ContainerBuilder $container, string $bundleName): bool
    {
        $bundles = $container->getParameter('kernel.bundles');

        $bundleNameOnly = str_replace('bundle', '', mb_strtolower($bundleName));
        $fullBundleName = ucfirst($bundleNameOnly).'Bundle';

        return isset($bundles[$fullBundleName]);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function isProd(ContainerBuilder $container): bool
    {
        return 'prod' === $container->getParameter('kernel.environment');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function isDebug(ContainerBuilder $container): bool
    {
        return $container->getParameter('kernel.debug');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function isDebugOrNotProd(ContainerBuilder $container): bool
    {
        return $this->isDebug($container) || !$this->isProd($container);
    }
}
