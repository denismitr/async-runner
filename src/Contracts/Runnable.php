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

    public function getOutput();

    public function getErrorOutput();

    public function triggerSuccess(): void;

    public function triggerError(): void;

    public function triggerTimeout(): void;

    public function getCurrentExecutionTime(): float;
}