# php-olest

## a pure PHP lib for continuous testing of a Jedox OLAP with phpunit

This repository is **unstable**. Please be careful when updating your app.
Some APIs/methods might break or change. If you use the library for
professional work you should either fork the version you develop with or
refer to a specific commit.

## Installation

Requires PHP 7.3+

```cli
composer require xodej/php-olest:dev-master
```

## Run tests

```cli
./vendor/bin/phpunit --bootstrap vendor/autoload.php tests/AdminTest
```

## Example
```php
<?php
// file ./tests/AdminTest.php
declare(strict_types=1);
namespace Xodej\Olest\Test;

include_once __DIR__ . '/../vendor/autoload.php';

use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;
use Xodej\Olapi\Connection;

class AdminTest extends OlapTestCase
{
    // define test
    public function testAdminIsAdmin(): void
    {
        // create a connection to the Jedox OLAP
        $connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');
        $cube = $connection->getCube('System/#_USER_GROUP');

        // test that admin user is assigned to admin group
        $this->assertOlapEquals(
            1,
            new CubeNumParam($cube, ['admin', 'admin']),
            'admin user is not assigned to admin group'
        );
    }
}
```

## Documentation

For more examples please look [here](./docs/index.md).

## License

Licensed under the [MIT](./LICENSE) License.