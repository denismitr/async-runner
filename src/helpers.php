<?php

use Denismitr\Async\Pool;
use Denismitr\Async\Contracts\Runnable;
use Denismitr\Async\Runtime\ParentRuntime;
use Denismitr\Async\TaskAbstract;

if (! function_exists('async')) {
    /**
     * @param TaskAbstract|callable $task
     * @return Runnable
     */
    function async($task): Runnable
    {
        return ParentRuntime::createProcess($task);
    }

    if (! function_exists('await')) {
        /**
         * @param Pool $pool
         * @return array
         */
        function await(Pool $pool): array
        {
            return $pool->wait();
        }
    }
}