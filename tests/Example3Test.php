<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionSingleton;
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
        $connection = ConnectionSingleton::getConnection('test_conn', 'http://127.0.0.1:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        self::$numberFormatDecimals = 3;

        $this->olestAssertEqualsWithDelta(
            -28124787.77,
            new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)']),
            '%1$$ / delta is %3$0.4f',
            0.001
        );
    }
}
