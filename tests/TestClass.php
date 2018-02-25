<?php

namespace Denismitr\Async\Tests;


class TestClass
{
    public $property = null;

    /**
     * @throws TestException
     */
    public function throwException()
    {
        throw new TestException('test');
    }
}