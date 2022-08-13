<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Tideways\Apm;

use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class RequestDataProvider
{
    private RequestFactoryInterface $requestFactory;

    public function __construct(RequestFactoryInterface $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    public function getSymfonyRequest(SwooleRequest $request): SymfonyRequest
    {
        return $this->requestFactory->make($request);
    }

    public function getDeveloperSession(SymfonyRequest $request): ?string
    {
        $developerSession = null;

        if ($request->query->has('_tideways')) {
            $developerSession = http_build_query((array) $request->query->get('_tideways'));
        } elseif ($request->headers->has('X-TIDEWAYS-PROFILER')) {
            $developerSession = $request->headers->get('X-TIDEWAYS-PROFILER');
        } elseif ($request->cookies->has('TIDEWAYS_SESSION')) {
            $developerSession = $request->cookies->get('TIDEWAYS_SESSION');
        }

        return is_string($developerSession) ? $developerSession : null;
    }

    public function getReferenceId(SymfonyRequest $request): ?string
    {
        $referenceId = $request->query->get('_tideways_ref', $request->headers->get('X-Tideways-Ref'));

        if ($request->cookies->has('TIDEWAYS_REF')) {
            $referenceId = $request->cookies->get('TIDEWAYS_REF');
        }

        return is_string($referenceId) ? $referenceId : null;
    }
}
