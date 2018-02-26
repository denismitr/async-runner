<?php

namespace Denismitr\Async;

/**
 * Class TaskAbstract
 * @package Denismitr\Async
 */
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