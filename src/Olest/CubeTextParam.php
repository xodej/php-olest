<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class CubeTextParam.
 */
class CubeTextParam extends AbstractCubeParam
{
    /**
     * @throws \Exception
     *
     * @return null|string
     */
    public function getValue(): ?string
    {
        if (null === $return = $this->getCube()->getValueC($this->coordinates)) {
            return null;
        }

        return (string) $return;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'string';
    }
}
