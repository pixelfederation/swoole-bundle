<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\RequestCycle;

/**
 *
 */
interface TerminatorInterface
{
    /**
     *
     */
    public function terminate(): void;
}
