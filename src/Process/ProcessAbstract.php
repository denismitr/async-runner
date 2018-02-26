<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 26.02.2018
 * Time: 12:49
 */

namespace Denismitr\Async\Process;


use Denismitr\Async\Contracts\Runnable;

abstract class ProcessAbstract implements Runnable
{
    /**
     * @var callable[]
     */
    protected $successCallbacks = [];

    /**
     * @var callable[]
     */
    protected $errorCallbacks = [];

    /**
     * @var callable[]
     */
    protected $timeoutCallbacks = [];

    abstract protected function resolveErrorOutput();

    /**
     * @param callable $callback
     * @return Runnable
     */
    public function then(callable $callback): Runnable
    {
        $this->successCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     * @return Runnable
     */
    public function catch(callable $callback): Runnable
    {
        $this->errorCallbacks[] = $callback;

        return $this;
    }

    /**
     * @param callable $callback
     * @return Runnable
     */
    public function timeout(callable $callback): Runnable
    {
        $this->timeoutCallbacks[] = $callback;

        return $this;
    }

    /**
     * @return mixed
     */
    public function triggerSuccess()
    {
        if ($this->getErrorOutput()) {
            $this->triggerError();

            return;
        }

        $output = $this->getOutput();

        foreach ($this->successCallbacks as $callback) {
            call_user_func_array($callback, [$output]);
        }

        return $output;
    }

    public function triggerError(): void
    {
        $exception = $this->resolveErrorOutput();

        foreach ($this->errorCallbacks as $callback) {
            call_user_func_array($callback, [$exception]);
        }

        if (!$this->errorCallbacks) {
            throw $exception;
        }
    }

    public function triggerTimeout(): void
    {
        foreach ($this->timeoutCallbacks as $callback) {
            call_user_func_array($callback, []);
        }
    }
}