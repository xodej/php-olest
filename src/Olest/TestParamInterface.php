<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Interface TestParamInterface.
 */
interface TestParamInterface
{
    public function getValue();

    public function getType(): string;

    public function getCoordinates(): array;
}
