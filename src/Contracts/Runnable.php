<?php

namespace Denismitr\Async\Contracts;


interface Runnable
{
    public function getId(): int;

    public function getPid(): ?int;

    public function start(): Runnable;

    public function then(callable $cb): Runnable;

    public function catch(callable $cb): Runnable;

    public function timeout(callable $cb): Runnable;

    public function stop(): Runnable;

    /**
     * @return mixed
     */
    public function getOutput();

    /**
     * @return mixed
     */
    public function getErrorOutput();

    /**
     * @return mixed
     */
    public function triggerSuccess();

    public function triggerError(): void;

    public function triggerTimeout(): void;

    public function getCurrentExecutionTime(): float;
}