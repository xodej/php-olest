# Test Jedox cubes

In the below discussed example Jedox shall have a cube named `Employees` in the database `Company` which holds three dimensions: `Year`, `Month` and `Department`.

Let's further assume that in `Jan` `2019` exactly `100` people worked in the `Controlling` department.

Now we can write a test to assure that the system returns the correct amount of `100` employees. Whenever the system returns another number for these coordinates we want our test to fail (and ideally be notified).

```php
// create a connection to Jedox OLAP
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 100 people work in controlling in Jan. 2019
$this->olestAssertEquals(
    100,
    new TestParamCubeNum($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019'
);
```

Theses tests can potentially run every night or every x hours to assure a certain degreee of data quality.

## Enhance the test with "almost" equality

While the first example shows the concept it lacks some features that will ease the process with "real" tests.

Real data is often not mathematically __equal__ because the way how computers store values internally it i s not yet possible.

To assure that your data is at least almost equal you can use `olestAssertEqualWithDelta()`.

```php
// create a connection to Jedox
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 99.999 < #VALUE < 100.001 people work in controlling in Jan. 2019
// so that the test succeeds even if Jedox returns 100.00000004 employees
$this->olestAssertEqualsWithDelta(
    100,
    new TestParamCubeNum($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019',
    0.001
);
```

## Make error messages more dynamic

If you test for multiple years and multiple months in a single test you probably want to have your error messages a bit more insightful without bloating the code so that you know what exactly broke the test.

The way how php-olest treats the OLAP calls in the backend to be faster and more efficient should not be counter

A build-in functionality allows you to choose between pre-defined templates.

`{!1}` - expected value in 1,000.00 notation  
`{!2}` - actual value in 1,000.00 notation  
`{!3}` - difference between actual and expected value in 1,000.00 notation

`{%1}` - expected value in 0.00% notation  
`{%2}` - actual value in 0.00% notation  
`{%3}` - difference between actual and expected value in 0.00% notation

`{1}` - if expected value is numeric then equal to `{!1}` otherwise the raw value  
`{2}` - if actual value is numeric then equal to `{!2}` otherwise the raw value  
`{3}` - analogue to `{!3}` or raw value

The precison of the notation can be changed with `self::$numberFormatDecimals` which is `2` by default.

```php
// create a connection to Jedox
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// increase precision for messages from 2 to 5
self::$numberFormatDecimals = 5;

// set the coordinates to query on the OLAP
$coordinate = ['2019', 'Jan', 'Controlling'];

// test if 99.999 < #VALUE < 100.001 people work in controlling in Jan. 2019
// so that the test succeeds even if Jedox returns 100.00000000000004 employees
$this->olestAssertEqualsWithDelta(
    100,
    new TestParamCubeNum($cube, $coordinate),
    sprintf('Found {!2} instead of expected {!1} employees for coordinate %s. Delta of {!3}.',
        implode(' / ', $coordinate)
    ),
    0.001
);
```

## Combine two OLAP calls

You can also compare two cubes e.g. CubeA and CubeB with each other for particular coordinates that should be the same.

This could make sense e.g. when HR department uses a different cube layout than the Finance department. Both hold information regarding employees but in total they should match.

```php
// create a connection to Jedox
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube_FI = $connection->getCube('Company/Employees');
$cube_HR = $connection->getCube('HR/Salaries');

$coordinate_FI = ['2019', 'Jan', 'Controlling'];
$coordinate_HR = ['2019', '01', 'FI-CO', 'Employees'];
$this->olestAssertEqualsWithDelta(
    new TestParamCubeNum($cube_HR, $coordinate_HR),
    new TestParamCubeNum($cube_FI, $coordinate_FI),
    sprintf('Found {!2} employees in FI instead of expected {!1} in HR for coordinate %s. Delta of {!3}.',
        implode(' / ', $coordinate_FI)
    ),
    0.001
);
```

## Add or subtract values from the actual

To compare two cubes it's sometimes required to reflect also a different model. One solution could be to harmonize the model - if this is no option you can try to harmonize the models in the test.

An example could be that a company has different definitions of what is believed to be the same. While logistics does not care about when an order happened but only when it's shipped the marketing department may be only interested in the orders that happened regardless of the fact if they have been shipped.

To bridge these two worlds between the data models one can `add()` or `subtract()` values from each other during a test.

The idea is to have a generic solution that matches all departments together. In an ideal world one would also set up tests with absolute numbers for each months - to not compare zeros against each other and still have suceeding tests.

```php
// create a connection to Jedox
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cubes
$cube_MA = $connection->getCube('Marketing/Orders');
$cube_LO = $connection->getCube('Logistic/Orders');
$cube_BI = $connection->getCube('BI/Orders');

// fetch orders from the marketing department "ordered orders"
$coordinate_MA = ['2019', 'Jan', 'DE', 'Orders'];

// fetch orders from the logistics department "shipped orders"
$coordinate_LO = ['2019', '01', 'D', 'Shipped Orders'];

// fetch order meta data from business intelligence department
$coordinate_BI = ['2019-01', 'GERMANY', 'Orders Not Shipped'];

// model: marketing orders - orders not shipped = shipped orders
$this->olestAssertEqualsWithDelta(
    (new TestParamCubeNum($cube_MA, $coordinate_MA))
        ->subtract(new TestParamCubeNum($cube_BI, $coordinate_BI)),
    new TestParamCubeNum($cube_LO, $coordinate_LO),
    sprintf('Found {!2} shipped orders in logistic instead of expected {!1} in marketing and business intelligence for coordinate %s. Delta of {!3}.',
        implode(' / ', $coordinate_MA)
    ),
    0.001
);
```

## Recycle connections

Since php-olest is a bit different from PHPUnit purpose wise it makes sense to recycle connections to the Jedox OLAP. This will lead to less HTTP requests since OLAP meta data like IDs, dimensions etc. does not need to be reloaded. Running tests in parallel can also lead to a shortage in licenses.

Therefore a `ConnectionSingleton` class is part of the library which holds a static connection throughout the entire test.

```php
// create a connection to Jedox
$connection = ConnectionSingleton::getConnection('prod', 'http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 99.999 < #VALUE < 100.001 people work in controlling in Jan. 2019
// so that the test succeeds even if Jedox returns 100.00000004 employees
$this->olestAssertEqualsWithDelta(
    100,
    new TestParamCubeNum($cube, ['2019', 'Jan', 'Controlling']),
    'Employees of controlling do not match expected 100 for Jan. 2019',
    0.001
);
```

## Do not use values from the OLAP in your test

While technically possible it will slow down your test suite by the factor of 100x or even more. Please do not `echo` or debug anything that is dynamically fetched from the OLAP in your tests.

Internally php-olest bundles all OLAP calls necessary to perform the asserts into a single HTTP request by putting them in a queue and process them all at once at the end of your test.

This is the main advantage that allows you to run asserts against 100.000 cells in a single test without major impact.

```php
// ***** AVOID SLOW TESTS *****

// create a connection to Jedox
$connection = new Connection('http://127.0.0.1:7777', 'admin', 'admin');

// retrieve the relevant cube
$cube = $connection->getCube('Company/Employees');

// test if 99.999 < #VALUE < 100.001 people work in controlling in Jan. 2019
// so that the test succeeds even if Jedox returns 100.00000004 employees
$this->olestAssertEqualsWithDelta(
    100,
     // SLOW TEST --> TRIGGERS IMMEDIATE HTTP REQUEST
     // 1.000 asserts lead to 1.000 HTTP requests
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

namespace Xodej\Olest\Test;

include_once __DIR__.'/../vendor/autoload.php';

use Xodej\Olest\ConnectionSingleton;
use Xodej\Olest\OlestTestCase;
use Xodej\Olest\TestParamCubeNum;

class Taxes2016BestBikeSellerTest extends OlestTestCase
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
            $this->olestAssertEqualsWithDelta(
                $expected,
                new TestParamCubeNum($cube, ['Actual', '2016', $variable_coordinate, '10 Best Bike Seller AG', 'Taxes on income']),
                'delta is {!3}',
                0.01
            );
        }
    }
}
```