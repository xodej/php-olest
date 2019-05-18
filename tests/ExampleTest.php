<?php

declare(strict_types=1);

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionSingleton;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

/**
 * Class ExampleTest.
 *
 * @internal
 * @coversNothing
 */
class ExampleTest extends OlapTestCase
{
    /**
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testExampleAdmin(): void
    {
        $connection = ConnectionSingleton::getConnection('test_conn', 'http://127.0.0.1:7777', 'admin', 'admin');
        $cube = $connection->getCube('System/#_USER_GROUP');

        $this->assertOlapEquals(
            1,
            new CubeNumParam($cube, ['admin', 'admin']),
            'admin user is not assigned to admin group'
        );
    }
}
