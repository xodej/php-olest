# Test Jedox cubes

In the below discussed example Jedox shall have a cube named `Employees` in the database `Company` which holds three dimensions: `Year`, `Month` and `Department`.

Let's further assume that in `Jan` `2019` exactly `100` people worked in the `Controlling` department.

Now we can write a test to assure that the system returns the correct amount of `100` employees. Whenever the system returns another number for these coordinates we want our test to fail (and ideally be notified).

```php
// create a connection to Jedox OLAP
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 100 people work in controlling in Jan. 2019
$this->assertOlapEquals(
    100,
    new CubeNumParam($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019'
);
```

Theses tests can potentially run every night or every x hours to assure a certain degreee of data quality.

## Enhance the test with "almost" equality

__Real data is often not mathematically equal__ because the way how computers store values internally it i s not yet possible.

To assure that your data is at least almost equal you can use the method `OlapTestCase::assertOlapAlmostEquals()`.

```php
// create a connection to Jedox
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 99.999 < #VALUE < 100.001 people work in controlling in Jan. 2019
// the test will succeed even if the OLAP returns 100.00000004 employees
$this->assertOlapAlmostEquals(
    100,
    new CubeNumParam($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019',
    0.001
);
```

## Make error messages more dynamic

If you test for multiple years and multiple months in a single test you probably want to have your error messages a bit more insightful without bloating the code so that you know what exactly broke the test.

A build-in functionality allows you to choose between pre-defined templates.

`%1$$` - expected value in 1,000.00 notation  
`%2$$` - actual value in 1,000.00 notation  
`%3$$` - difference between actual and expected value in 1,000.00 notation  
`%4$$` - cube coordinates of expected (only usable with cube calls)  
`%5$$` - cube coordinates of actual (only usable with cube calls)  

The precision of the notation can be changed with `self::$numberFormatDecimals` which is `2` by default.

```php
// create a connection to Jedox
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// increase precision for messages from 2 to 5
self::$numberFormatDecimals = 5;

// set the coordinates to query on the OLAP
$coordinate = ['2019', 'Jan', 'Controlling'];

$this->assertOlapAlmostEquals(
    100,
    new CubeNumParam($cube, $coordinate),
    'Found %2$$ instead of expected %1$$ employees for coordinate %s. Delta of %3$$. %4$$ vs. %5$$',
    0.001
);
```

## Combine two OLAP calls

You can also compare two cubes e.g. CubeA and CubeB with each other for particular coordinates that should be the same.

This becomes useful when e.g. HR department uses a different cube layout than the Finance department. Both hold information regarding employees in a different way but in total they should match.

```php
// create a connection to Jedox
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube_FI = $connection->getCube('Company/Employees');
$cube_HR = $connection->getCube('HR/Salaries');

$coordinate_FI = ['2019', 'Jan', 'Controlling'];
$coordinate_HR = ['2019', '01', 'FI-CO', 'Employees'];

$this->assertOlapAlmostEquals(
    new CubeNumParam($cube_HR, $coordinate_HR),
    new CubeNumParam($cube_FI, $coordinate_FI),
    'Found %2$$ employees in FI instead of expected %1$$ in HR for coordinate %s. Delta of %3$$. %4$$ vs. %5$$',
    0.001
);
```

## Add or subtract dynamic values

To compare two cubes it's sometimes required to reflect a different model.

An example could be that a company has different definitions of what is believed to be the same like the amount of orders. While logistics does not care about when an order was placed but only when it is shipped the marketing department may be only interested in the orders that were placed regardless of the status.

`[orders shipped] = [orders placed] - [orders not shipped]`

With the methods `CubeNumParam::add()` and/or `CubeNumParam::subtract()` one is able to model these relationships in the test without having an immediate HTTP call to the OLAP.

```php
// create a connection to Jedox
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cubes
$cube_marketing = $connection->getCube('Marketing/Orders');
$cube_logistics = $connection->getCube('Logistic/Orders');
$cube_bi_team   = $connection->getCube('BI/Orders');

// fetch orders from the marketing department "orders placed"
$coord_marketing = ['2019', 'Jan', 'DE', 'Orders'];

// fetch orders from the logistics department "orders shipped"
$coord_logistics = ['2019', '01', 'D', 'Shipped Orders'];

// fetch orders from the business intelligence department "orders not shipped"
$coord_bi_team   = ['2019-01', 'GERMANY', 'Orders Not Shipped'];

// model: [orders shipped] = [orders placed] - [orders not shipped]
$this->assertOlapAlmostEquals(
    new CubeNumParam($cube_logistics, $coord_logistics),
    (new CubeNumParam($cube_marketing, $coord_marketing))
        ->subtract(new CubeNumParam($cube_bi_team, $coord_bi_team)),
    'Calculated %2$$ shipped orders instead of expected %1$$ in logistics for coordinate %4$$. Delta of %3$$.',
    0.001
);
```

## Tip I - Recycle connections

Since php-olest is a bit different from the purpose of PHPUnit it is recommended to recycle the OLAP connections. This will lead to less HTTP requests since OLAP meta data like IDs, dimensions etc. does not need to be reloaded. Setting up new connections for every test can also lead to a shortage of licenses.

Therefore a `ConnectionFactory` class is part of the library which holds a static connection throughout the entire test.

```php
// create a connection to Jedox
$connection = ConnectionFactory::getConnection('prod', 'http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

$this->assertOlapAlmostEquals(
    100,
    new CubeNumParam($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019',
    0.001
);
```

## Tip II - Do not use values from the OLAP in your test

While technically possible it will slow down your test suite significantly. Internally php-olest bundles all assert related OLAP requests into a single HTTP request where possible.

This approach allows you to run asserts against 100.000 cells in a single test without major impact.

```php
// **************************
// ***** SLOW TEST DEMO *****

// use ConnectionFactory::getConnection() instead
$connection = new Connection('http://localhost:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

$this->assertOlapAlmostEquals(
    100,
     // Cube::getValue() TRIGGERS IMMEDIATE HTTP REQUEST
     // 1.000 asserts = 1.000 HTTP requests
    $cube->getValue(['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019',
    0.001
);
```

## Example of a complete test

```php
<?php
// file: ./tests/Taxes2016BestBikeSellerTest.php
declare(strict_types=1);

use Xodej\Olest\ConnectionFactory;
use Xodej\Olest\OlapTestCase;
use Xodej\Olest\CubeNumParam;

class Taxes2016BestBikeSellerTest extends OlapTestCase
{
    public function testTaxes2016BestBikeSeller(): void
    {
        $connection = ConnectionFactory::getConnection('test_conn', 'http://localhost:7777', 'admin', 'admin');
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
                new CubeNumParam($cube, ['Actual', '2016', $variable_coordinate, '10 Best Bike Seller AG', 'Taxes on income']),
                'delta is %3$$',
                0.01
            );
        }
    }
}
```