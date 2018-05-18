<?php

namespace Denismitr\Async\Tests\Stubs;


use Denismitr\Async\AsyncTask;

class TestAsyncTask extends AsyncTask
{
    protected $value;
    protected $sleep;

    public function __construct($value, $sleep = 500)
    {
        $this->value = $value;
        $this->sleep = $sleep;
    }

    public function run()
    {
        usleep($this->sleep);

        return $this->value;
    }
}