<?php


namespace Denismitr\Async\Process;


use Denismitr\Async\Exceptions\ParallelException;
use Denismitr\Async\Exceptions\SerializableException;
use Throwable;
use Denismitr\Async\Contracts\Runnable;
use Symfony\Component\Process\Process;

class ParallelProcess extends ProcessAbstract
{
    /**
     * @var Process
     */
    protected $process;
    /**
     * @var int
     */
    protected $id;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var int
     */
    protected $pid;

    protected $output;
    protected $errorOutput;

    /**
     * ParallelProcess constructor.
     * @param Process $process
     * @param int $id
     */
    public function __construct(Process $process, int $id)
    {
        $this->process = $process;
        $this->id = $id;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }

    /**
     * @param Process $process
     * @param int $id
     * @return ParallelProcess
     */
    public static function create(Process $process, int $id): self
    {
        return new self($process, $id);
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
        return $this->pid;
    }

    /**
     * @return Runnable
     */
    public function start(): Runnable
    {
        $this->startTime = microtime(true);

        $this->process->start();

        $this->pid = $this->process->getPid();

        return $this;
    }

    /**
     * @return Runnable
     */
    public function stop(): Runnable
    {
        $this->process->stop(10, SIGKILL);

        return $this;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    /**
     * @return bool
     */
    public function isTerminated(): bool
    {
        return $this->process->isTerminated();
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        if (! $this->output) {
            $processOutput = $this->process->getOutput();

            $this->output = @unserialize(base64_decode($processOutput));

            if (! $this->output) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->output;
    }

    public function getErrorOutput()
    {
        if (! $this->errorOutput) {
            $processOutput = $this->process->getErrorOutput();

            $this->errorOutput = @unserialize(base64_decode($processOutput));

            if (! $this->errorOutput) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->errorOutput;
    }

    /**
     * @return float
     */
    public function getCurrentExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * @return Throwable
     */
    protected function resolveErrorOutput(): Throwable
    {
        $exception = $this->getErrorOutput();

        if ($exception instanceof SerializableException) {
            $exception = $exception->asThrowable();
        }

        if (! $exception instanceof Throwable) {
            $exception = ParallelException::fromException($exception);
        }

        return $exception;
    }
}