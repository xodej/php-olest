<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class AnyParam.
 */
class AnyParam implements TestParamInterface
{
    /** @var mixed */
    protected $value;

    /**
     * TestParamFix constructor.
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return gettype($this->getValue());
    }
}
