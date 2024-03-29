<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class FloatParam.
 */
class FloatParam implements TestParamInterface
{
    /** @var float */
    protected $value;

    /**
     * TestParamDecimal constructor.
     *
     * @param float $value
     */
    public function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'double';
    }

    /**
     * @return string[]
     */
    public function getCoordinates(): array
    {
        return ['Float<' . $this->value . '>'];
    }
}
