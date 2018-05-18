# PHP Async Task Runner

[![Build Status](https://travis-ci.org/denismitr/wait.svg?branch=master)](https://travis-ci.org/denismitr/wait)

### Run PHP tasks asynchronously with the PCNTL extension

### Usage

```php
$wg = WaitGroup::create();
$profit = 0;

foreach (range(1, 10) as $i) {
    $wg->add(function () {
        return 5;
    })->then(function (int $result) use (&$profit) {
        $profit += $result;
    });
}

$wg->wait();

echo $profit; // 50
```
Example with AsyncTask inheritance
```php
// Create a class(es) that inherit from AsyncTask
use Denismitr\Async\AsyncTask;

class TestAsyncTask1 extends AsyncTask
{
    public function __construct($passSomething)
    {
        // Some initialization here
    }

    public function run()
    {
        usleep(1000); // some async action

        return 'some result';
    }
}

// Run

$wg = WaitGroup::create();

$wg[] = async(new TestAsyncTask1($passSomething));
$wg[] = async(new TestAsyncTask2($passSomething));

$results = await($wg);

$results[0]; // Result of TestAsyncTask1
$results[1]; // Result of TestAsyncTask2
```