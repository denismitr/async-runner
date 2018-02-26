<?php

use Denismitr\Async\WaitGroup;
use Denismitr\Async\Contracts\Runnable;
use Denismitr\Async\Runtime\RuntimeManager;
use Denismitr\Async\TaskAbstract;

if (! function_exists('async')) {
    /**
     * @param TaskAbstract|callable $task
     * @return Runnable
     */
    function async($task): Runnable
    {
        return RuntimeManager::createProcess($task);
    }

    if (! function_exists('await')) {
        /**
         * @param WaitGroup $wg
         * @return array
         */
        function await(WaitGroup $wg): array
        {
            return $wg->wait();
        }
    }
}