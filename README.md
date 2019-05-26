# php-olest

## a pure PHP lib for (continuous) testing of a Jedox OLAP

This repository is **unstable**. Please be careful when updating your app.
Some APIs/methods might break or change. If you use the library for
professional work you should either fork the version you develop with or
refer to a specific commit.

Based on [phpunit](https://phpunit.de/).

## Installation

Requires PHP 7.3+

```cli
composer require xodej/php-olapi:dev-master
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

use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;
use Xodej\Olest\ConnectionSingleton;

class AdminTest extends OlapTestCase
{
    // define test
    public function testAdminIsAdmin(): void
    {
        // establish connection with Jedox OLAP
        $connection = ConnectionSingleton::getConnection('prod', 'http://127.0.0.1:7777', 'admin', 'admin');
        $cube = $connection->getCube('System/#_USER_GROUP');

        // assert that user "admin" is assigned to user group "admin" 
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