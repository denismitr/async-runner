<?php

namespace Denismitr\Async;

/**
 * Class AsyncTask
 * @package Denismitr\Async
 */
abstract class AsyncTask
{
    abstract function run();

    public function __invoke()
    {
        return $this->run();
    }
}