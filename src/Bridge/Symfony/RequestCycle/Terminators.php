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
final class Terminators
{
    /**
     * @var TerminatorInterface[]|iterable
     */
    private $terminators;

    /**
     * @param iterable|TerminatorInterface[] $terminators
     */
    public function __construct($terminators)
    {
        $this->terminators = $terminators;
    }

    /**
     *
     */
    public function terminate(): void
    {
        foreach ($this->terminators as $terminator) {
            $terminator->terminate();
        }
    }
}
