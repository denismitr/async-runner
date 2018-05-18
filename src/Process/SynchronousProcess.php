<?php

namespace Denismitr\Async\Process;


use Denismitr\Async\Exceptions\SerializableException;
use Denismitr\Async\AsyncTask;
use Throwable;
use Denismitr\Async\Contracts\Runnable;

/**
 * Class SynchronousProcess
 * @package Denismitr\Async\Process
 */
class SynchronousProcess extends ProcessAbstract
{
    /**
     * @var int
     */
    protected $id;

    protected $task;
    protected $output;
    protected $errorOutput;

    /**
     * @var float
     */
    protected $executionTime;

    /**
     * SynchronousProcess constructor.
     * @param $id
     * @param $task
     */
    public function __construct(callable $task, int $id)
    {
        $this->id = $id;
        $this->task = $task;
    }

    /**
     * @param callable $task
     * @param int $id
     * @return SynchronousProcess
     */
    public static function create(callable $task, int $id): self
    {
        return new self($task, $id);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->getId();
    }

    /**
     * @return Runnable
     */
    public function start(): Runnable
    {
        try {
            $startTime = microtime(true);

            $this->output = $this->task instanceof AsyncTask
                ? $this->task->run()
                : call_user_func($this->task);

            $this->executionTime = microtime(true) - $startTime;
        } catch (Throwable $t) {
            $this->errorOutput = $t;
        }

        return $this;
    }

    /**
     * @return Runnable
     */
    public function stop(): Runnable
    {
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return mixed
     */
    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    /**
     * @return float
     */
    public function getCurrentExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * @return mixed|Throwable
     */
    protected function resolveErrorOutput()
    {
        $exception = $this->getErrorOutput();

        if ($exception instanceof SerializableException) {
            $exception = $exception->asThrowable();
        }

        return $exception;
    }
}