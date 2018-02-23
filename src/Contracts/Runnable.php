<?php

namespace Denismitr\Async\Contracts;


interface Runnable
{
    public function getId(): int;

    public function getPid(): ?int;

    public function start(): void;

    public function then(callable $cb);

    public function catch(callable $cb);

    public function timeout(callable $cb);

    public function stop(): void;

    public function getOutput();

    public function getErrorOutput();

    public function triggerSuccess();

    public function triggerError();

    public function triggerTimeout();

    public function getCurrentExecutionTime(): float;
}