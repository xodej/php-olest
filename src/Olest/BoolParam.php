<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class BoolParam.
 */
class BoolParam implements TestParamInterface
{
    /** @var bool */
    protected $value;

    /**
     * TestParamBool constructor.
     *
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function getValue(): bool
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'boolean';
    }

    /**
     * @return string[]
     */
    public function getCoordinates(): array
    {
        return ['Bool<' . $this->value . '>'];
    }
}
