<?php
// file: ./tests/Taxes2016BestBikeSellerTest.php
declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionSingleton;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

class Taxes2016BestBikeSellerTest extends OlapTestCase
{
    public function testTaxes2016BestBikeSeller(): void
    {
        $connection = ConnectionSingleton::getConnection('test_conn', 'http://127.0.0.1:7777', 'admin', 'admin');
        $cube = $connection->getCube('Biker/P_L');

        // all taxes on income in 2016 per month
        $tests = [
            // 'variable_coordinate' => 'expected'
            'Jan' => 27976.61,
            'Feb' => 41734.24,
            'Mar' => 74850.92,
            'Apr' => -75661.40,
            'May' => -46968.62,
            'Jun' => -25900.38,
            'Jul' => 116405.14,
            'Aug' => -45451.43,
            'Sep' => -26134.57,
            'Oct' => 0,
            'Nov' => 0,
            'Dec' => 0
        ];

        // testing all 12 months in one test
        // making a single HTTP call to OLAP
        foreach ($tests as $variable_coordinate => $expected) {
            $this->assertOlapAlmostEquals(
                $expected,
                new CubeNumParam($cube, ['Actual', '2016', $variable_coordinate, '10 Best Bike Seller AG', 'Taxes on income']), // THIS IS RECOMMENDED -> 1 HTTP request
                // (float) $cube->getValue(['Actual', '2016', $variable_coordinate, '10 Best Bike Seller AG', 'Taxes on income']), // THIS IS SLOW -> 12 HTTP requests
                'delta is %3$$',
                0.01
            );
        }
    }
}