<?php

namespace Denismitr\Async\Tests\Stubs;


class Invokable
{
    public function __invoke()
    {
        return 2;
    }
}