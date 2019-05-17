<?php

declare(strict_types=1);

namespace Xodej\Olest;

use PHPUnit\Framework\TestCase;

/**
 * Class OlapTestCase.
 */
abstract class OlapTestCase extends TestCase
{
    // public static $chunkSize = 1000; // not used --> palo query chunk size

    protected const TYPE_EQUALS = 1;
    protected const TYPE_TRUE = 2;
    protected const TYPE_FALSE = 3;
    protected const TYPE_NOT_EQUALS = 4;
    protected const TYPE_EQUALS_WITH_DELTA = 5;

    /** @var null|int */
    public static $blockPrintSize = 5;

    /** @var int */
    public static $numberFormatDecimals = 2;

    /** @var \ArrayObject */
    protected $testQueue;

    /** @var AbstractCubeParam[] */
    protected $cubeCoordQueue;

    /** @var string[] */
    protected $failures;

    /**
     * OlestTestCase constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->testQueue = new \ArrayObject();
        $this->cubeCoordQueue = new \ArrayObject();
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
    public function olestAssertEquals($expected, $actual, string $message = ''): void
    {
        if (\is_scalar($expected)) {
            $expected = new AnyParam($expected);
        }

        if (\is_scalar($actual)) {
            $actual = new AnyParam($actual);
        }

        if (!($expected instanceof TestParamInterface) || !($actual instanceof TestParamInterface)) {
            throw new \Exception('unsupported type '.gettype($expected).' or '.gettype($actual).' used in olestAssertEquals()');
        }

        // add
        $this->_cubeQueueing($expected);
        $this->_cubeQueueing($actual);

        $this->testQueue[self::TYPE_EQUALS][] = [$expected, $actual, $message];
    }

    /**
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     *
     * @throws \Exception
     */
    public function olestAssertEqualsWithDelta($expected, $actual, string $message = '', float $delta = 0.001): void
    {
        if (\is_scalar($expected)) {
            $expected = new AnyParam($expected);
        }

        if (\is_scalar($actual)) {
            $actual = new AnyParam($actual);
        }

        if (!($expected instanceof TestParamInterface) || !($actual instanceof TestParamInterface)) {
            throw new \Exception('unsupported type '.gettype($expected).' or '.gettype($actual).' used in olestAssertEqualsWithDelta()');
        }

        // add
        $this->_cubeQueueing($expected);
        $this->_cubeQueueing($actual);

        $this->testQueue[self::TYPE_EQUALS_WITH_DELTA][] = [$expected, $actual, $message, $delta];
    }

    /**
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @throws \Exception
     */
    public function olestAssertNotEquals($expected, $actual, string $message = ''): void
    {
        if (\is_scalar($expected)) {
            $expected = new AnyParam($expected);
        }

        if (\is_scalar($actual)) {
            $actual = new AnyParam($actual);
        }

        if (!($expected instanceof TestParamInterface) || !($actual instanceof TestParamInterface)) {
            throw new \Exception('unsupported type '.gettype($expected).' or '.gettype($actual).' used in olestAssertNotEquals()');
        }

        // add
        $this->_cubeQueueing($expected);
        $this->_cubeQueueing($actual);

        $this->testQueue[self::TYPE_NOT_EQUALS][] = [$expected, $actual, $message];
    }

    /**
     * @param mixed  $actual
     * @param string $message
     *
     * @throws \Exception
     */
    public function olestAssertTrue($actual, string $message = ''): void
    {
        if (\is_scalar($actual)) {
            $actual = new AnyParam($actual);
        }

        if (!($actual instanceof TestParamInterface)) {
            throw new \Exception('unsupported type '.gettype($actual).' used in olestAssertTrue()');
        }

        // add
        $this->_cubeQueueing($actual);

        $this->testQueue[self::TYPE_TRUE][] = [$actual, $message];
    }

    /**
     * @param mixed  $actual
     * @param string $message
     *
     * @throws \Exception
     */
    public function olestAssertFalse($actual, string $message = ''): void
    {
        if (\is_scalar($actual)) {
            $actual = new AnyParam($actual);
        }

        if (!($actual instanceof TestParamInterface)) {
            throw new \Exception('unsupported type '.gettype($actual).' used in olestAssertFalse()');
        }

        // add
        $this->_cubeQueueing($actual);

        $this->testQueue[self::TYPE_FALSE][] = [$actual, $message];
    }

    public function clearCache(): void
    {
        $this->failures = new \ArrayObject();
        $this->testQueue = new \ArrayObject();
        $this->cubeCoordQueue = new \ArrayObject();
    }

    /**
     * @param TestParamInterface $param
     *
     * @return bool
     */
    protected function _cubeQueueing(TestParamInterface $param): bool
    {
        // collect calls to cubes for fast cached calls
        if ($param instanceof AbstractCubeParam) {
            $this->cubeCoordQueue[] = $param;
            // $this->_flushQueue();
            return true;
        }

        return false;
    }

    protected function _addAddsAndSubsToQueue(): void
    {
        // add 1st level of subs and adds to cached-call-queue
        $tmp = $this->cubeCoordQueue;
        foreach ($tmp as $cube) {
            // skip cube calls that return text
            if (!($cube instanceof CubeNumParam)) {
                continue;
            }

            if ($cube->hasAdd()) {
                foreach ($cube->getAdd() as $nestedParamObj) {
                    $this->_cubeQueueing($nestedParamObj);
                }
            }
            if ($cube->hasSubtract()) {
                foreach ($cube->getSubtract() as $nestedParamObj) {
                    $this->_cubeQueueing($nestedParamObj);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function _flushQueue(): void
    {
        // add subs and adds to caching-queue
        $this->_addAddsAndSubsToQueue();

        // run for pre-fetching in cache
        $cubes = [];
        foreach ($this->cubeCoordQueue as $preFetch) {
            $cube = $preFetch->getCube();
            $cubes[$cube->getOlapObjectId()] = $cube;

            $cube->startCache(true);
            $cube->getValueC($preFetch->getCoordinates());
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
        $this->_flushQueue();

        // run tests in queue
        foreach ($this->testQueue as $type => $tests) {
            switch ($type) {
                case self::TYPE_EQUALS:
                    $this->runEquals($tests);

                    break;
                case self::TYPE_TRUE:
                    $this->runTrue($tests);

                    break;
                case self::TYPE_FALSE:
                    $this->runFalse($tests);

                    break;
                case self::TYPE_NOT_EQUALS:
                    $this->runNotEquals($tests);

                    break;
                case self::TYPE_EQUALS_WITH_DELTA:
                    $this->runEqualsWithDelta($tests);

                    break;
            }
        }

        return $this->failures;
    }

    protected function printOut(): void
    {
        // print out errors
        if (0 !== $this->failures->count()) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                $this->failures->count()." assertions failed:\n\t".\implode("\n\t", $this->failures->getArrayCopy())
            );
        }
    }

    /**
     * @param array $tests
     */
    protected function runTrue(array $tests): void
    {
        $i = 0;
        foreach ($tests as $test) {
            try {
                self::assertTrue($test[0]->getValue());
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                ++$i;
                $msg = $test[1];
                // collect messages
                if (null !== self::getBlockPrintSize() && 0 === $i % self::getBlockPrintSize()) {
                    $msg .= PHP_EOL;
                }
                $this->failures[] = $msg;
            }
        }
    }

    /**
     * @param array $tests
     */
    protected function runFalse(array $tests): void
    {
        $i = 0;
        foreach ($tests as $test) {
            try {
                self::assertFalse($test[0]->getValue());
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                ++$i;
                $msg = $test[1];
                // collect messages
                if (null !== self::getBlockPrintSize() && 0 === $i % self::getBlockPrintSize()) {
                    $msg .= PHP_EOL;
                }
                $this->failures[] = $msg;
            }
        }
    }

    /**
     * @param array $tests
     */
    protected function runNotEquals(array $tests): void
    {
        $this->runEquals($tests, true);
    }

    /**
     * @param array $tests
     */
    protected function runEqualsWithDelta(array $tests): void
    {
        $this->runEquals($tests, false, true);
    }

    /**
     * @return null|int
     */
    protected static function getBlockPrintSize(): ?int
    {
        if (null === self::$blockPrintSize || 0 === self::$blockPrintSize) {
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
     * @param array     $tests
     * @param null|bool $invertLogic
     * @param null|bool $withDelta
     */
    protected function runEquals(array $tests, ?bool $invertLogic = null, ?bool $withDelta = null): void
    {
        $invertLogic = $invertLogic ?? false;
        $withDelta = $withDelta ?? false;

        $i = 0;
        foreach ($tests as $test) {
            try {
                if ($invertLogic) {
                    self::assertNotEquals($test[0]->getValue(), $test[1]->getValue());
                } elseif ($withDelta) {
                    self::assertEqualsWithDelta($test[0]->getValue(), $test[1]->getValue(), $test[3]);
                } else {
                    self::assertEquals($test[0]->getValue(), $test[1]->getValue());
                }
            } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
                ++$i;

                $msg = (string) $test[2];

                if (false !== \strpos($msg, '%')) {
                    $expected_value = $test[0]->getValue();
                    $actual_value = $test[1]->getValue();

                    // handle custom %1$$ pattern for number_format()
                    $msg = (string) \preg_replace_callback('/(\%[1-3]\$\$)/', static function ($match) use ($expected_value, $actual_value): string {
                        if (\is_numeric($expected_value) && '%1$$' === $match[1]) {
                            return \number_format($expected_value, (int) self::getNumberFormatDecimals(), ',', '.');
                        }

                        if (\is_numeric($actual_value) && '%2$$' === $match[1]) {
                            return \number_format($actual_value, (int) self::getNumberFormatDecimals(), ',', '.');
                        }

                        if (\is_numeric($expected_value) && \is_numeric($actual_value) && '%3$$' === $match[1]) {
                            return \number_format($actual_value - $expected_value, (int) self::getNumberFormatDecimals(), ',', '.');
                        }

                        return $match[1];
                    }, $msg);

                    // handle all other patterns
                    if (\is_numeric($expected_value) && \is_numeric($actual_value)) {
                        $msg = \sprintf($msg, $expected_value, $actual_value, $actual_value - $expected_value);
                    } else {
                        $msg = \sprintf($msg, $expected_value, $actual_value);
                    }
                }

                // collect messages
                if (null !== self::getBlockPrintSize() && 0 === $i % self::getBlockPrintSize()) {
                    $msg .= PHP_EOL;
                }
                $this->failures[] = $msg;
            }
        }
    }
}
