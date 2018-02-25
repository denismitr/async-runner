<?php

namespace Denismitr\Async\Tests;


class Invokable
{
    public function __invoke()
    {
        return 2;
    }
}