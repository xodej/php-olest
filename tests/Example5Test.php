<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionFactory;
use Xodej\Olest\FloatParam;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

/**
 * Class ExampleTest5.
 *
 * @internal
 * @coversNothing
 */
class Example5Test extends OlapTestCase
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

        $this->assertOlapAlmostEquals(
            -28124785.77,
            (new CubeNumParam($cube, ['Variance', 'All Years', 'Qtr.1', '501 Omega Group', 'Net income / (loss)']))
                ->add(new FloatParam(2.0)),
            '%1$$ / delta is %3$0.4f',
            0.01
        );
    }
}
