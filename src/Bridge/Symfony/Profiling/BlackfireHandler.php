<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\Profiling;

use Blackfire\Client;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 *
 */
final class BlackfireHandler implements RequestHandlerInterface
{
    /**
     * @var RequestHandlerInterface
     */
    private $decorated;

    /**
     * @param RequestHandlerInterface $decorated
     */
    public function __construct(RequestHandlerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, Response $response): void
    {
        $enableProfiling = isset($request->header['x-blackfire-query']);

        if (!$enableProfiling) {
            $this->decorated->handle($request, $response);

            return;
        }

        $blackfireClient = new Client();
        $probe = $blackfireClient->createProbe();
        $this->decorated->handle($request, $response);
        $blackfireClient->endProbe($probe);
    }
}
