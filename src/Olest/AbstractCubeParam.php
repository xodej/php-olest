<?php

declare(strict_types=1);

namespace Xodej\Olest;

use Xodej\Olapi\Cube;

/**
 * Class AbstractCubeParam.
 */
abstract class AbstractCubeParam implements TestParamInterface
{
    /** @var Cube */
    protected $cube;

    /** @var array */
    protected $coordinates;

    /**
     * TestParamCube constructor.
     *
     * @param Cube  $cube
     * @param array $coordinates
     */
    public function __construct(Cube $cube, array $coordinates)
    {
        $this->cube = $cube;
        $this->coordinates = $coordinates;
    }

    /**
     * @return Cube
     */
    public function getCube(): Cube
    {
        return $this->cube;
    }

    /**
     * @return array
     */
    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'NULL';
    }

    /**
     * @return bool
     */
    public function hasAdd(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasSubtract(): bool
    {
        return false;
    }
}
