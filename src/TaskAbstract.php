<?php

namespace Denismitr\Async;


abstract class TaskAbstract
{
    abstract public function configure();

    abstract function run();

    public function __invoke()
    {
        $this->configure();

        return $this->run();
    }
}