<?php

namespace Denismitr\Async\Exceptions;

use Throwable;

class SerializableException
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $trace;

    /**
     * SerializableException constructor.
     * @param Throwable $t
     */
    public function __construct(Throwable $t)
    {
        $this->class = get_class($t);
        $this->message = $t->getMessage();
        $this->trace = $t->getTraceAsString();
    }

    /**
     * @return Throwable
     */
    public function asThrowable(): Throwable
    {
        /** @var Throwable $throwable */
        $throwable = new $this->class($this->message . "\n\n" . $this->trace);

        return $throwable;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getTrace(): string
    {
        return $this->trace;
    }
}