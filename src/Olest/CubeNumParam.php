<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class CubeNumParam.
 */
class CubeNumParam extends AbstractCubeParam
{
    /** @var TestParamInterface[] */
    protected $_add = [];

    /** @var TestParamInterface[] */
    protected $_sub = [];

    /**
     * @throws \Exception
     *
     * @return null|float|int|string
     */
    public function getValue()
    {
        $subTotal = 0;
        $addTotal = 0;

        if ($this->hasSubtract()) {
            foreach ($this->_sub as $sub) {
                $subTotal -= $sub->getValue();
            }

            if (!\is_numeric($subTotal)) {
                $subTotal = 0;
            }
        }

        if ($this->hasAdd()) {
            foreach ($this->_add as $add) {
                $addTotal += $add->getValue();
            }

            if (!\is_numeric($addTotal)) {
                $addTotal = 0;
            }
        }

        $return = $this->getCube()->getValueC($this->coordinates);
        if (\is_numeric($return)) {
            return $return + $subTotal + $addTotal;
        }

        if ('#NA' === $return || null === $return) {
            return 0;
        }

        return $return;
    }

    /**
     * @param TestParamInterface $param
     *
     * @return CubeNumParam
     */
    public function add(TestParamInterface $param): self
    {
        if ('string' === $param->getType()) {
            throw new \InvalidArgumentException('text can not be used with TestParamCubeNum::add()');
        }

        $this->_add[] = $param;

        return $this;
    }

    /**
     * @param TestParamInterface $param
     *
     * @return CubeNumParam
     */
    public function subtract(TestParamInterface $param): self
    {
        if ('string' === $param->getType()) {
            throw new \InvalidArgumentException('text can not be used with TestParamCubeNum::subtract()');
        }

        $this->_sub[] = $param;

        return $this;
    }

    /**
     * @return TestParamInterface[]
     */
    public function getAdd(): array
    {
        return $this->_add ?? [];
    }

    /**
     * @return TestParamInterface[]
     */
    public function getSubtract(): array
    {
        return $this->_sub ?? [];
    }

    /**
     * @return bool
     */
    public function hasAdd(): bool
    {
        return isset($this->_add[0]);
    }

    /**
     * @return bool
     */
    public function hasSubtract(): bool
    {
        return isset($this->_sub[0]);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return 'double';
    }
}
