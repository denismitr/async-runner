<?php

namespace Denismitr\Async\Process;


use Denismitr\Async\TaskAbstract;
use Throwable;
use Denismitr\Async\Contracts\Runnable;

class SynchronousProcess implements Runnable
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

    use Callbacks;

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

    public function start(): Runnable
    {
        try {
            $startTime = microtime(true);

            $this->output = $this->task instanceof TaskAbstract
                ? $this->task->run()
                : call_user_func($this->task);

            $this->executionTime = microtime(true) - $startTime;
        } catch (Throwable $t) {
            $this->errorOutput = $t;
        }

        return $this;
    }

    public function stop(): Runnable
    {
        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    public function getCurrentExecutionTime(): float
    {
        return $this->executionTime;
    }
}