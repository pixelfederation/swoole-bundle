<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation\Session;

use K911\Swoole\Bridge\Symfony\Event\SessionResetEvent;
use K911\Swoole\Server\Session\StorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;

/**
 * Sets the session in the request.
 */
final class SetSessionFactoryCookieEventListener implements EventSubscriberInterface
{
    use SessionCookieEventListenerTrait;

    private RequestStack $requestStack;

    private EventDispatcherInterface $dispatcher;

    public function __construct(
        RequestStack $stack,
        EventDispatcherInterface $dispatcher,
        StorageInterface $swooleStorage,
        array $sessionOptions = []
    ) {
        $this->requestStack = $stack;
        $this->swooleStorage = $swooleStorage;
        $this->dispatcher = $dispatcher;
        $this->sessionCookieParameters = $this->mergeCookieParams($sessionOptions);
    }

    public function onFinishRequest(FinishRequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->isSessionRelated($event)) {
            return;
        }

        if ($this->session()->isStarted()) {
            $this->dispatcher->dispatch(
                new SessionResetEvent($this->session()->getId()),
                SessionResetEvent::NAME
            );
        }

        $this->swooleStorage->garbageCollect();
    }
}
