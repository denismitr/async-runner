# PHP Async Task Runner

[![Build Status](https://travis-ci.org/denismitr/wait.svg?branch=master)](https://travis-ci.org/denismitr/wait)

### Run PHP tasks asynchronously with the PCNTL extension

### Under development

```php
$wg = WaitGroup::make();
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