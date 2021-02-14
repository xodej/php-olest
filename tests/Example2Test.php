<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionFactory;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

/**
 * Class ExampleTest2.
 *
 * @internal
 * @coversNothing
 */
class Example2Test extends OlapTestCase
{
    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleAdmin2(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('System/#_USER_GROUP');

        $this->assertOlapEquals(
            1,
            new CubeNumParam($cube, ['admin', 'admin']),
            'admin user is not assigned to admin group {!1} / {!2}'
        );
    }

    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testAdminNotDesigner(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
        $cube = $connection->getCube('System/#_USER_GROUP');

        $this->assertOlapNotEquals(
            1,
            new CubeNumParam($cube, ['admin', 'designer']),
            'admin user is not assigned to admin group'
        );
    }
}
