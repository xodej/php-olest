<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionFactory;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

/**
 * Class Example4Test.
 *
 * @internal
 * @coversNothing
 */
class Example4Test extends OlapTestCase
{
    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleNumber4(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        self::$numberFormatDecimals = 5;

        $this->assertOlapEqualsWithDelta(
            27976.61272,
            new CubeNumParam($cube, ['Actual', '2016', 'Jan', '10 Best Bike Seller AG', 'Taxes on income']),
            'delta is %3$$',
            0.001
        );
    }
}
