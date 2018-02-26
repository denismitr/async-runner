<?php

namespace Denismitr\Async;


use Denismitr\Async\Exceptions\SerializableException;
use Denismitr\Async\Process\ParallelProcess;

class State
{
    /**
     * @var WaitGroup
     */
    private $wg;

    /**
     * State constructor.
     * @param WaitGroup $wg
     */
    public function __construct(WaitGroup $wg)
    {
        $this->wg = $wg;
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
        $queue = $this->wg->getQueue();
        $finished = $this->wg->getFinished();
        $failed = $this->wg->getFailed();
        $timeouts = $this->wg->getTimeouts();

        return
            'queue: '.count($queue)
            .' - finished: '.count($finished)
            .' - failed: '.count($failed)
            .' - timeouts: '.count($timeouts);
    }

    /**
     * @return string
     */
    protected function failedToString(): string
    {
        return (string) array_reduce($this->wg->getFailed(), function ($currentState, ParallelProcess $process) {
            $output = $process->getErrorOutput();

            if ($output instanceof SerializableException) {
                $output = get_class($output->asThrowable()) . ': ' . $output->asThrowable()->getMessage();
            }

            return $this->lines((string) $currentState, "{$process->getPid()} failed with {$output}");
        });
    }
}