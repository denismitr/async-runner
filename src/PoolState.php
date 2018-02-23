<?php

namespace Denismitr\Async;


class PoolState
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * PoolState constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->lines(
            $this->summaryToString(),
            $this->failedToString()
        );
    }

    /**
     * @param string[] ...$lines
     * @return string
     */
    protected function lines(string ...$lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    /**
     * @return string
     */
    protected function summaryToString(): string
    {
        $queues = $this->pool->getQueues();
        $finished = $this->pool->getFinished();
        $failed = $this->pool->getFailed();
        $timeouts = $this->pool->getTimeouts();

        return
            'queues: '.count($queues)
            .' - finished: '.count($finished)
            .' - failed: '.count($failed)
            .' - timeouts: '.count($timeouts);
    }

    protected function failedToString(): string
    {
        return (string) array_reduce($this->pool->getFailed(), function ($currentState, $process) {

        });
    }
}