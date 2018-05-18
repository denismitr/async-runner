<?php

namespace Denismitr\Async\Tests\Stubs;


class Invokable
{
    private $value;

    /**
     * Invokable constructor.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __invoke()
    {
        return $this->value;
    }
}