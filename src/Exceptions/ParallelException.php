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
     * @param int $bytes
     * @return ParallelException
     */
    public static function outputTooLarge(int $bytes): self
    {
        return new self(
            "The output returned by this child process is too large. The serialized output may only be $bytes bytes long."
        );
    }
}