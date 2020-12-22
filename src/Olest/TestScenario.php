<?php

declare(strict_types=1);

namespace Xodej\Olest;

/**
 * Class TestScenario.
 */
class TestScenario
{
    protected $scenario_type;
    protected $expected;
    protected $actual;
    protected $message;
    protected $coordinates;
    protected $delta;

    /**
     * TestScenario constructor.
     *
     * @param int $scenario_type
     */
    protected function __construct(int $scenario_type)
    {
        $this->scenario_type = $scenario_type;
    }

    /**
     * @return int
     */
    public function getScenarioType(): int
    {
        return $this->scenario_type;
    }

    /**
     * @param int                                      $scenario_type
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     * @param float                                    $delta
     *
     * @return TestScenario
     */
    public static function getTestScenarioActualAndExpected(int $scenario_type, $expected, $actual, string $message = '', float $delta = 0.001): TestScenario
    {
        $expected = \is_scalar($expected) ? new AnyParam($expected) : $expected;
        $actual = \is_scalar($actual) ? new AnyParam($actual) : $actual;

        $ts_object = new self($scenario_type);
        $ts_object->setExpected($expected);
        $ts_object->setActual($actual);
        $ts_object->setMessage($message);
        $ts_object->setDelta($delta);

        return $ts_object;
    }

    /**
     * @param int                                      $scenario_type
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     *
     * @return TestScenario
     */
    public static function getTestScenarioActual(int $scenario_type, $actual, string $message = ''): TestScenario
    {
        $actual = \is_scalar($actual) ? new AnyParam($actual) : $actual;

        $ts_object = new self($scenario_type);
        $ts_object->setActual($actual);
        $ts_object->setMessage($message);

        return $ts_object;
    }

    /**
     * @param TestParamInterface $expected
     */
    public function setExpected(TestParamInterface $expected): void
    {
        $this->expected = $expected;
    }

    /**
     * @return TestParamInterface|null
     */
    public function getExpected(): ?TestParamInterface
    {
        return $this->expected;
    }

    /**
     * @param TestParamInterface $actual
     */
    public function setActual(TestParamInterface $actual): void
    {
        $this->actual = $actual;
    }

    /**
     * @return TestParamInterface
     */
    public function getActual(): TestParamInterface
    {
        return $this->actual;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param float $delta
     */
    public function setDelta(float $delta): void
    {
        $this->delta = $delta;
    }

    /**
     * @return float
     */
    public function getDelta(): float
    {
        if (null === $this->delta) {
            return 0.001;
        }

        return $this->delta;
    }

    /**
     * @param array $coordinates
     */
    public function setOlapCoordinates(array $coordinates): void
    {
        $this->coordinates = $coordinates;
    }
}
