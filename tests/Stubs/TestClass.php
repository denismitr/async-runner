<?php

namespace Denismitr\Async\Tests\Stubs;


use Denismitr\Async\Tests\Exceptions\TestException;

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