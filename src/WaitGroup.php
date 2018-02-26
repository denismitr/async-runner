<?php

namespace Denismitr\Async;


use ArrayAccess;
use Denismitr\Async\Process\ParallelProcess;
use Denismitr\Async\Process\SynchronousProcess;
use Denismitr\Async\Runtime\ParentRuntime;
use InvalidArgumentException;
use Denismitr\Async\Contracts\Runnable;

class WaitGroup implements ArrayAccess
{
    /** @var bool */
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

    /** @var State */
    protected $state;

    /**
     * WaitGroup constructor.
     */
    public function __construct()
    {
        $this->registerListener();

        $this->state = new State($this);
    }

    /**
     * @return WaitGroup
     */
    public static function make(): WaitGroup
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
     * @return WaitGroup
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param int $concurrency
     * @return WaitGroup
     */
    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * @param string $autoloader
     * @return WaitGroup
     */
    public function autoload(string $autoloader): self
    {
        ParentRuntime::init($autoloader);

        return $this;
    }

    /**
     * @param int $sleepTime
     * @return WaitGroup
     */
    public function sleepTime(int $sleepTime): self
    {
        $this->sleepTime = $sleepTime;

        return $this;
    }

    /**
     * @param $process
     * @return Runnable
     */
    public function add($process): Runnable
    {
        if ( ! is_callable($process) && ! $process instanceof Runnable) {
            throw new InvalidArgumentException(
                "The process passed to WaitGroup::add should be callable or implement the Runnable interface."
            );
        }

        if ( ! $process instanceof Runnable ) {
            $process = ParentRuntime::createProcess($process);
        }

        $this->putInQueue($process);

        return $process;
    }

    /**
     * @param callable|null $intermediateCallback
     * @return array
     */
    public function wait(?callable $intermediateCallback = null): array
    {
        while ($this->inProgress) {
            foreach ($this->inProgress as $process) {
                if ($process->getCurrentExecutionTime() > $this->timeout) {
                    $this->markAsTimeout($process);
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

    /**
     * @param Runnable $process
     */
    public function putInProgress(Runnable $process): void
    {
        if ($process instanceof ParallelProcess) {
            $process->getProcess()->setTimeout($this->timeout);
        }

        $process->start();

        unset($this->queue[$process->getId()]);

        $this->inProgress[$process->getPid()] = $process;
    }

    /**
     * @param Runnable $process
     */
    public function markAsFinished(Runnable $process)
    {
        unset($this->inProgress[$process->getPid()]);

        $this->update();

        $this->results[] = $process->triggerSuccess();
        $this->finished[$process->getPid()] = $process;
    }

    /**
     * @param Runnable $process
     */
    public function markAsTimeout(Runnable $process)
    {
        unset($this->inProgress[$process->getPid()]);

        $this->update();

        $process->triggerTimeout();

        $this->timeouts[$process->getPid()] = $process;
    }

    /**
     * @param Runnable $process
     */
    public function markAsFailed(Runnable $process): void
    {
        unset($this->inProgress[$process->getPid()]);

        $this->update();

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

    public function offsetSet($offset, $task)
    {
        $this->add($task);
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    /**
     * @param Runnable $process
     */
    public function putInQueue(Runnable $process): void
    {
        $this->queue[$process->getId()] = $process;

        $this->update();
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

    /**
     * @return State
     */
    public function state(): State
    {
        return $this->state;
    }

    protected function update(): void
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