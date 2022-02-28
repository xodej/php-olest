<?php

declare(strict_types=1);

namespace Xodej\Olest;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Xodej\Olapi\Cube;

/**
 * Class OlapTestCase.
 */
abstract class OlapTestCase extends TestCase
{
    protected const TYPE_EQUALS = 1;
    protected const TYPE_TRUE = 2;
    protected const TYPE_FALSE = 4;
    protected const TYPE_NOT_EQUALS = 8;
    protected const TYPE_EQUALS_WITH_DELTA = 16;
    protected const TYPE_LESS_THAN = 32;
    protected const TYPE_LESS_THAN_OR_EQUAL = 64;
    protected const TYPE_GREATER_THAN = 128;
    protected const TYPE_GREATER_THAN_OR_EQUAL = 256;

    /** @var null|int */
    public static $blockPrintSize = 5;

    /** @var int */
    public static $numberFormatDecimals = 2;

    /** @var int */
    protected static $counter = 0;

    /** @var TestScenario[] */
    protected $testQueue;

    /** @var string[] */
    protected $failures;

    /**
     * OlapTestCase constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        self::$counter = 0;

        $this->testQueue = new \ArrayObject();
        $this->failures = new \ArrayObject();
    }

    /**
     * @throws \Exception
     */
    public function tearDown(): void
    {
        // run collected tests
        $this->runTests();
        $this->printOut();
        $this->clearCache();
        parent::tearDown();
    }

    /**
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @throws \Exception
     */
    public function assertOlapEquals($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_EQUALS, $expected, $actual, $message);
    }

    /**
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     *
     * @throws \Exception
     */
    public function assertOlapEqualsWithDelta($expected, $actual, string $message = '', float $delta = 0.001): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_EQUALS_WITH_DELTA, $expected, $actual, $message, $delta);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     * @param float                                    $delta
     *
     * @throws \Exception
     */
    public function assertOlapAlmostEquals($expected, $actual, string $message = '', float $delta = 0.001): void
    {
        $this->assertOlapEqualsWithDelta($expected, $actual, $message, $delta);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     */
    public function assertOlapGreaterThan($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_GREATER_THAN, $expected, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     */
    public function assertOlapGreaterThanOrEqual($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_GREATER_THAN_OR_EQUAL, $expected, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     */
    public function assertOlapLessThan($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_LESS_THAN, $expected, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     */
    public function assertOlapLessThanOrEqual($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_LESS_THAN_OR_EQUAL, $expected, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $expected
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     *
     * @throws \Exception
     */
    public function assertOlapNotEquals($expected, $actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActualAndExpected(self::TYPE_NOT_EQUALS, $expected, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     *
     * @throws \Exception
     */
    public function assertOlapTrue($actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActual(self::TYPE_TRUE, $actual, $message);
    }

    /**
     * @param bool|float|int|string|TestParamInterface $actual
     * @param string                                   $message
     *
     * @throws \Exception
     */
    public function assertOlapFalse($actual, string $message = ''): void
    {
        $this->testQueue[] = TestScenario::getTestScenarioActual(self::TYPE_FALSE, $actual, $message);
    }

    public function clearCache(): void
    {
        $this->failures = new \ArrayObject();
        $this->testQueue = new \ArrayObject();
    }

    /**
     * @throws \Exception
     */
    protected function _flushQueue(): void
    {
        $cubes = [];

        foreach ($this->testQueue as $testScenario) {
            // if expected requires an OLAP call
            if ($testScenario->getExpected() instanceof AbstractCubeParam) {
                /** @var Cube $cube */
                $cube = $testScenario->getExpected()->getCube();
                $cubes[\spl_object_hash($cube)] = $cube;

                $cube->startCache(true);

                $cube->getValueC($testScenario->getExpected()->getCoordinates());

                // CubeNumParam can potentially have add() and subtract()
                if ($testScenario->getExpected() instanceof CubeNumParam) {
                    // crawl through adds of expected
                    foreach ($testScenario->getExpected()->getAdd() as $nestedParamObj) {
                        if ($nestedParamObj instanceof CubeNumParam) {
                            /** @var Cube $addCube */
                            $addCube = $nestedParamObj->getCube();
                            $cubes[\spl_object_hash($addCube)] = $addCube;

                            $addCube->startCache(true);
                            $addCube->getValueC($nestedParamObj->getCoordinates());
                        }
                    }

                    // crawl through subtracts of expected
                    foreach ($testScenario->getExpected()->getSubtract() as $nestedParamObj) {
                        if ($nestedParamObj instanceof CubeNumParam) {
                            /** @var Cube $subCube */
                            $subCube = $nestedParamObj->getCube();
                            $cubes[\spl_object_hash($subCube)] = $subCube;

                            $subCube->startCache(true);
                            $subCube->getValueC($nestedParamObj->getCoordinates());
                        }
                    }
                } // instanceof CubeNumParam
            } // expected

            // if actual requires an OLAP call
            if ($testScenario->getActual() instanceof AbstractCubeParam) {
                /** @var Cube $cube */
                $cube = $testScenario->getActual()->getCube();
                $cubes[\spl_object_hash($cube)] = $cube;

                $cube->startCache(true);

                $cube->getValueC($testScenario->getActual()->getCoordinates());

                // CubeNumParam can potentially have add() and subtract()
                if ($testScenario->getActual() instanceof CubeNumParam) {
                    // crawl through adds of actual
                    foreach ($testScenario->getActual()->getAdd() as $nestedParamObj) {
                        if ($nestedParamObj instanceof CubeNumParam) {
                            /** @var Cube $addCube */
                            $addCube = $nestedParamObj->getCube();
                            $cubes[\spl_object_hash($addCube)] = $addCube;

                            $addCube->startCache(true);
                            $addCube->getValueC($nestedParamObj->getCoordinates());
                        }
                    }

                    // crawl through subtracts of actual
                    foreach ($testScenario->getActual()->getSubtract() as $nestedParamObj) {
                        if ($nestedParamObj instanceof CubeNumParam) {
                            /** @var Cube $subCube */
                            $subCube = $nestedParamObj->getCube();
                            $cubes[\spl_object_hash($subCube)] = $subCube;

                            $subCube->startCache(true);
                            $subCube->getValueC($nestedParamObj->getCoordinates());
                        }
                    }
                } // instanceof CubeNumParam
            } // actual
        }

        foreach ($cubes as $cube) {
            $cube->endCache();
        }
    }

    /**
     * @throws \Exception
     *
     * @return \ArrayObject
     */
    protected function runTests(): \ArrayObject
    {
        // trigger the one single HTTP request to OLAP
        $this->_flushQueue();

        // run tests in queue
        foreach ($this->testQueue as $test) {
            switch ($test->getScenarioType()) {
                case self::TYPE_EQUALS:
                case self::TYPE_NOT_EQUALS:
                case self::TYPE_EQUALS_WITH_DELTA:
                case self::TYPE_LESS_THAN:
                case self::TYPE_LESS_THAN_OR_EQUAL:
                case self::TYPE_GREATER_THAN:
                case self::TYPE_GREATER_THAN_OR_EQUAL:
                    $this->runCompareTest($test);

                    break;
                case self::TYPE_TRUE:
                case self::TYPE_FALSE:
                    $this->runBoolTest($test);

                    break;
            }
        }

        return $this->failures;
    }

    protected function printOut(): void
    {
        // print out errors
        if (0 !== $this->failures->count()) {
            throw new ExpectationFailedException(
                $this->failures->count()." assertions failed:\n\t".\implode("\n\t", $this->failures->getArrayCopy())
            );
        }
    }

    /**
     * @param TestScenario $test
     */
    protected function runBoolTest(TestScenario $test): void
    {
        try {
            switch ($test->getScenarioType()) {
                case self::TYPE_TRUE:
                    self::assertTrue($test->getActual()->getValue());

                    break;
                case self::TYPE_FALSE:
                    self::assertFalse($test->getActual()->getValue());

                    break;
            }
        } catch (ExpectationFailedException $e) {
            ++self::$counter;
            $msg = $test->getMessage();
            // collect messages
            if (null !== self::getBlockPrintSize() && 0 === self::$counter % self::getBlockPrintSize()) {
                $msg .= PHP_EOL;
            }
            $this->failures[] = $msg;
        }
    }

    /**
     * @return null|int
     */
    protected static function getBlockPrintSize(): ?int
    {
        if (null === self::$blockPrintSize || 0 === (int) self::$blockPrintSize) {
            return null;
        }

        return (int) self::$blockPrintSize;
    }

    /**
     * @return int
     */
    protected static function getNumberFormatDecimals(): int
    {
        return (int) self::$numberFormatDecimals;
    }

    /**
     * @param TestScenario $test
     */
    protected function runCompareTest(TestScenario $test): void
    {
        try {
            switch ($test->getScenarioType()) {
                case self::TYPE_NOT_EQUALS:
                    self::assertNotEquals($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
                case self::TYPE_EQUALS_WITH_DELTA:
                    self::assertEqualsWithDelta($test->getExpected()->getValue(), $test->getActual()->getValue(), $test->getDelta());

                    break;
                case self::TYPE_EQUALS:
                    self::assertEquals($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
                case self::TYPE_LESS_THAN:
                    self::assertLessThan($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
                case self::TYPE_LESS_THAN_OR_EQUAL:
                    self::assertLessThanOrEqual($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
                case self::TYPE_GREATER_THAN:
                    self::assertGreaterThan($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
                case self::TYPE_GREATER_THAN_OR_EQUAL:
                    self::assertGreaterThanOrEqual($test->getExpected()->getValue(), $test->getActual()->getValue());

                    break;
            }
        } catch (ExpectationFailedException $e) {
            ++self::$counter;

            $msg = $test->getMessage();

            if (false !== \strpos($msg, '%')) {
                $expected_value = $test->getExpected()->getValue();
                $actual_value = $test->getActual()->getValue();

                // handle custom %1$$ pattern for number_format()
                $msg = (string) \preg_replace_callback('/(\%[1-5]\$\$)/', static function ($match) use ($expected_value, $actual_value, $test): string {
                    if (\is_numeric($expected_value) && '%1$$' === $match[1]) {
                        return \number_format($expected_value, self::getNumberFormatDecimals(), ',', '.');
                    }

                    if (\is_numeric($actual_value) && '%2$$' === $match[1]) {
                        return \number_format($actual_value, self::getNumberFormatDecimals(), ',', '.');
                    }

                    if (\is_numeric($expected_value) && \is_numeric($actual_value) && '%3$$' === $match[1]) {
                        return \number_format($actual_value - $expected_value, self::getNumberFormatDecimals(), ',', '.');
                    }

                    if ('%4$$' === $match[1] && $test->getExpected() instanceof AbstractCubeParam) {
                        return \is_array($test->getExpected()->getCoordinates()) ? \str_replace('%', '%%', \implode(' / ', $test->getExpected()->getCoordinates())) : '';
                    }

                    if ('%5$$' === $match[1] && $test->getActual() instanceof AbstractCubeParam) {
                        return \is_array($test->getActual()->getCoordinates()) ? \str_replace('%', '%%', \implode(' / ', $test->getActual()->getCoordinates())) : '';
                    }

                    return $match[1];
                }, $msg);

                // handle all other patterns
                if (\is_numeric($expected_value) && \is_numeric($actual_value)) {
                    $msg = \sprintf($msg, $expected_value, $actual_value, $actual_value - $expected_value);
                } else {
                    $msg = \sprintf($msg, \str_replace('%', '%%', $expected_value), \str_replace('%', '%%', $actual_value));
                }
            }

            // collect messages
            if (null !== self::getBlockPrintSize() && 0 === self::$counter % self::getBlockPrintSize()) {
                $msg .= PHP_EOL;
            }
            $this->failures[] = $msg;
        }
    }
}
