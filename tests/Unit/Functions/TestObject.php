<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

/**
 * Class TestObject.
 *
 * @property string $dynamicProp
 */
#[\AllowDynamicProperties]
class TestObject
{
    public const GOOD_VALUE = 'good';
    public const WRONG_VALUE = 'wrong';
    public $publicProp;
    protected $protectedProp;

    private $privateProp;

    public function __construct(string $value = self::WRONG_VALUE)
    {
        $this->privateProp = $value;
        $this->protectedProp = $value;
        $this->publicProp = $value;
        $this->dynamicProp = $value;
    }

    public function getPrivateProp(): string
    {
        return $this->privateProp;
    }

    public function getProtectedProp(): string
    {
        return $this->protectedProp;
    }

    public function getPublicProp(): string
    {
        return $this->publicProp;
    }

    public function getDynamicProp(): string
    {
        return $this->dynamicProp;
    }
}
