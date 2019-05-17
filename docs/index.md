# php-olest Documentation

## Installation

```cli
composer require xodej/php-olest:dev-master
```

If you already use PHPUnit you can install the package without the phpunit
source and binary.

```cli
composer require --no-dev xodej/php-olest:dev-master
```

## Run tests

```cli
./vendor/bin/phpunit --bootstrap vendor/autoload.php tests/AdminTest
```

## Why you should use this project

1. It's a pure PHP implementation - no dependencies exist to the C extension developed by Jedox itself (the data layer is php-olapi)

1. It's open source and free to use under the terms of the MIT license

1. It takes care of efficient data loading by bundling HTTP calls to the OLAP instead of firing one request per assert

1. All the assert features of PHPUnit are there

1. Even for non-technical people it's possible to write tests by following simple rules

## How to write tests

### Test Jedox cubes

php-olest is based on the PHPUnit de-facto standard package for testing PHP. Nevertheless
php-olest is using PHPUnit in a non-standard way. It is not using best-practice patterns.

Please read [here](./cubes.md) for more information on how to test Jedox cubes.

### Test Jedox dimensions

php-olest is based on the PHPUnit de-facto standard package for testing PHP. Nevertheless
php-olest is using PHPUnit in a non-standard way. It is not using best-practice patterns.

Please read [here](./dimensions.md) for more information on how to test Jedox dimensions.