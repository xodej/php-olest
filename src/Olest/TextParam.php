<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class TextParam.
 */
class TextParam implements TestParamInterface
{
    /** @var string */
    protected $value;

    /**
     * TestParamText constructor.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'string';
    }

    /**
     * @return string[]
     */
    public function getCoordinates(): array
    {
        return ['String<' . $this->value . '>'];
    }
}
