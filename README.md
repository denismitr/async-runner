# PHP Async Task Runner

[![Build Status](https://travis-ci.org/denismitr/wait.svg?branch=master)](https://travis-ci.org/denismitr/wait)

### Run PHP tasks asynchronously with the PCNTL extension

### Installation

```bash
composer require denismitr/async-runner
```

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

You can check the result of each task by id, to help preserve the order
```php
$wg = WaitGroup::create();

$idA = $wg->add(new TestAsyncTask('foo'))->getId();
$idB = $wg->add(new TestAsyncTask('bar'))->getId();
$idC = $wg->add(new TestAsyncTask('baz'))->getId();

$results = $wg->wait();

$this->assertEquals('foo', $results[$idA]);
$this->assertEquals('bar', $results[$idB]);
$this->assertEquals('baz', $results[$idC]);
```

You can set max concurrent processes limit

```php
$wg = WaitGroup::create()->setMaxConcurrently(2);

$startTime = microtime(true);

foreach (range(1, 3) as $i) {
    $wg->add(function () {
        sleep(1);
    });
}

$wg->wait(); // Will run only 2 tasks in parallell, then the 3rd one
```

You can set a timeout
```php
$wg = WaitGroup::create()->setTimeout(3);

$timedOut = 0;

foreach (range(1, 5) as $i) {
    $wg->add(function () use ($i) {
        sleep($i);
    })->timeout(function () use (&$timedOut) {
        $timedOut += 1;
    });
}

$wg->wait();

$this->assertEquals(3, $timedOut);
```