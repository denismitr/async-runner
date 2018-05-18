# PHP Async Task Runner

[![Build Status](https://travis-ci.org/denismitr/wait.svg?branch=master)](https://travis-ci.org/denismitr/wait)

### Run PHP tasks asynchronously with the PCNTL extension

### Usage

```php
$wg = WaitGroup::create();
$profit = 0;

foreach (range(1, 10) as $i) {
    $wg->add(function () {
        usleep(200); // some action here that takes time
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
        usleep(1000); // some action here

        return 'some result';
    }
}

// Run

$wg = WaitGroup::create();

$wg->add(new TestAsyncTask1($passSomething));
$wg->add(new TestAsyncTask2($passSomething));

$results = $wg->wait();

foreach($results as $result) {
    // gives 2 results of 2 async tasks
}
```