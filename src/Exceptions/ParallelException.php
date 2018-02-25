<?php

namespace Denismitr\Async\Exceptions;

use Exception;

class ParallelException extends Exception
{
    /**
     * @param string $exception
     * @return ParallelException
     */
    public static function fromException($exception): self
    {
        return new self($exception);
    }

    /**
     * @param int $actual
     * @param int $max
     * @return ParallelException
     */
    public static function outputTooLarge(int $actual, int $max): self
    {
        return new self(
            "The output size of {$actual} bytes, returned by the child process is too large. The serialized output may only be {$max} bytes long."
        );
    }
}