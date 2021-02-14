<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionFactory;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

/**
 * Class ExampleTest3.
 *
 * @internal
 * @coversNothing
 */
class Example3Test extends OlapTestCase
{
    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleNumber(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        self::$numberFormatDecimals = 3;

        $this->assertOlapEqualsWithDelta(
            -28124787.7676,
            new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)']),
            '%1$$ / delta is %3$0.4f',
            0.001
        );
    }

    /**
     * @throws \ErrorException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleNumberAdd(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        self::$numberFormatDecimals = 3;

        $this->assertOlapEqualsWithDelta(
            -28124787.7676 * 2,
            (new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)']))
                ->add(new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)'])),
            '%1$$ / delta is %3$0.4f',
            0.001
        );
    }

    /**
     * @throws \ErrorException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleNumberSub(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        self::$numberFormatDecimals = 3;

        $this->assertOlapEqualsWithDelta(
            -28124787.7676 * 0,
            (new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)']))
                ->subtract(new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)'])),
            '%1$$ / delta is %3$0.4f',
            0.001
        );
    }
}
