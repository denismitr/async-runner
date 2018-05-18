<?php

namespace Denismitr\Async\Tests\Stubs;


use Denismitr\Async\AsyncTask;

class TestAsyncTask extends AsyncTask
{
    protected $i;

    public function __construct(int $i)
    {
        $this->i = $i;
    }

    function run()
    {
        return $this->i;
    }
}