<?php

namespace Denismitr\Async;


use ArrayAccess;

class Pool implements ArrayAccess
{
    public static $forceSync = false;

    protected $concurrency = 20;
    protected $tasksPerProcess = 1;
    protected $timeout = 300;
    protected $sleepTime = 50000;

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
}