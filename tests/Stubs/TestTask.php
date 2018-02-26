<?php

namespace Denismitr\Async\Tests\Stubs;


use Denismitr\Async\TaskAbstract;

class TestTask extends TaskAbstract
{
    protected $i = 0;

    public function configure()
    {
        $this->i = 2;
    }

    function run()
    {
        return $this->i;
    }
}