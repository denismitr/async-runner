<?php

namespace Denismitr\Async;


use ArrayAccess;
use Denismitr\Async\Process\ParallelProcess;
use Denismitr\Async\Process\SynchronousProcess;
use Denismitr\Async\Runtime\ParentRuntime;
use InvalidArgumentException;
use Denismitr\Async\Contracts\Runnable;

class Pool implements ArrayAccess
{
    public static $forceSync = false;

    protected $concurrency = 20;
    protected $tasksPerProcess = 1;
    protected $timeout = 300;
    protected $sleepTime = 50000;

    /** @var Runnable[] */
    protected $queue = [];

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
        $this->registerListener();

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

    /**
     * @param $process
     * @return Runnable
     */
    public function add($process): Runnable
    {
        if ( ! is_callable($process) && ! $process instanceof Runnable) {
            throw new InvalidArgumentException(
                "The process passed to Pool::add should be callable or implement the Runnable interface."
            );
        }

        if ( ! $process instanceof Runnable ) {
            $process = ParentRuntime::createProcess($process);
        }

        $this->putInQueue($process);

        return $process;
    }

    public function wait(?callable $intermediateCallback = null): array
    {
        while ($this->inProgress) {
            foreach ($this->inProgress as $process) {
                if ($process->getCurrentExecutionTime() > $this->timeout) {
                    $this->markAsFinished($process);
                }

                if ($process instanceof SynchronousProcess) {
                    $this->markAsFinished($process);
                }
            }

            if ( ! $this->inProgress) {
                break;
            }

            if ($intermediateCallback) {
                call_user_func_array($intermediateCallback, [$this]);
            }

            usleep($this->sleepTime);
        }

        return $this->results;
    }

    public function putInProgress(Runnable $process): void
    {
        if ($process instanceof ParallelProcess) {
            $process->getProcess()->setTimeout($this->timeout);
        }

        $process->start();

        unset($this->queue[$process->getId()]);

        $this->inProgress[$process->getPid()] = $process;
    }

    public function markAsFinished(Runnable $process)
    {
        unset($this->inProgress[$process->getPid()]);

        $this->notify();

        $this->results[] = $process->triggerSuccess();
        $this->finished[$process->getPid()] = $process;
    }

    public function markAsTimeout(Runnable $process)
    {
        unset($this->inProgress[$process->getPid()]);

        $this->notify();

        $process->triggerTimeout();

        $this->timeouts[$process->getPid()] = $process;
    }

    public function markAsFailed(Runnable $process): void
    {
        unset($this->inProgress[$process->getPid()]);

        $this->notify();

        $process->triggerError();

        $this->failed[$process->getPid()] = $process;
    }

    public function offsetExists($offset)
    {
        return false;
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

    public function putInQueue(Runnable $process): void
    {
        $this->queue[$process->getId()] = $process;

        $this->notify();
    }

    /**
     * @return Runnable[]
     */
    public function getQueue(): array
    {
        return $this->queue;
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

    public function state(): PoolState
    {
        return $this->state;
    }

    protected function registerListener()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGCHLD, function ($signo, $status) {
            while (true) {
                $pid = pcntl_waitpid(-1, $processState, WNOHANG | WUNTRACED);

                if ($pid <= 0) {
                    break;
                }

                $process = $this->inProgress[$pid] ?? null;

                if ( ! $process) {
                    continue;
                }

                if ($status['status'] === 0) {
                    $this->markAsFinished($process);

                    continue;
                }

                $this->markAsFailed($process);
            }
        });
    }
}