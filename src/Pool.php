<?php

namespace Denismitr\Async;


use ArrayAccess;
use Denismitr\Async\Contracts\Runnable;

class Pool implements ArrayAccess
{
    public static $forceSync = false;

    protected $concurrency = 20;
    protected $tasksPerProcess = 1;
    protected $timeout = 300;
    protected $sleepTime = 50000;

    /** @var Runnable[] */
    protected $queues = [];

    /** @var Runnable[] */
    protected $inProgress = [];

    /** @var Runnable[] */
    protected $finished = [];

    /** @var Runnable[] */
    protected $failed = [];

    /** @var Runnable[] */
    protected $timeouts = [];

    protected $results = [];

    /**
     * @var PoolState
     */
    protected $state;

    /**
     * Pool constructor.
     */
    public function __construct()
    {
        $this->state = new PoolState($this);
    }

    /**
     * @return Pool
     */
    public static function create(): Pool
    {
        return new static();
    }

    /**
     * @return bool
     */
    public static function isSupported(): bool
    {
        return
            function_exists('pcntl_async_signals')
            && function_exists('posix_kill')
            && ! self::$forceSync;
    }

    /**
     * @param int $timeout
     * @return Pool
     */
    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param int $concurrency
     * @return Pool
     */
    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * @param int $sleepTime
     * @return Pool
     */
    public function sleepTime(int $sleepTime): self
    {
        $this->sleepTime = $sleepTime;

        return $this;
    }

    public function notify(): void
    {
        if (count($this->inProgress) >= $this->concurrency) {
            return;
        }

        $process = array_shift($this->queue);

        if (!$process) {
            return;
        }

        $this->putInProgress($process);
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    /**
     * @return Runnable[]
     */
    public function getQueues(): array
    {
        return $this->queues;
    }

    /**
     * @return Runnable[]
     */
    public function getInProgress(): array
    {
        return $this->inProgress;
    }

    /**
     * @return Runnable[]
     */
    public function getFinished(): array
    {
        return $this->finished;
    }

    /**
     * @return Runnable[]
     */
    public function getFailed(): array
    {
        return $this->failed;
    }

    /**
     * @return Runnable[]
     */
    public function getTimeouts(): array
    {
        return $this->timeouts;
    }
}