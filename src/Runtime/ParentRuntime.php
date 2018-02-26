<?php

namespace Denismitr\Async\Runtime;

use Closure;
use Denismitr\Async\Contracts\Runnable;
use Denismitr\Async\WaitGroup;
use Denismitr\Async\Process\ParallelProcess;
use Denismitr\Async\Process\SynchronousProcess;
use Opis\Closure\SerializableClosure;
use function Opis\Closure\serialize;
use function Opis\Closure\unserialize;
use Symfony\Component\Process\Process;

class ParentRuntime
{
    /** @var bool */
    protected static $isInitialised = false;

    /** @var string */
    protected static $autoloader;

    /** @var string */
    protected static $childProcessScript;

    /**
     * @var int
     */
    protected static $currentId = 0;

    /**
     * @var null|string
     */
    protected static $myPid = null;

    /**
     * @param string|null $autoloader
     */
    public static function init(string $autoloader = null)
    {
        if ( ! $autoloader) {
            $existingAutoloaderFiles = array_filter([
                __DIR__.'/../../../../autoload.php',
                __DIR__.'/../../../autoload.php',
                __DIR__.'/../../vendor/autoload.php',
                __DIR__.'/../../../vendor/autoload.php',
            ], function (string $path) {
                return file_exists($path);
            });

            $autoloader = reset($existingAutoloaderFiles);
        }

        self::$autoloader = $autoloader;
        self::$childProcessScript = __DIR__.'/ChildRuntime.php';

        self::$isInitialised = true;
    }

    public static function createProcess($task): Runnable
    {
        if ( ! self::$isInitialised) {
            self::init();
        }

        if ( ! WaitGroup::isSupported()) {
            return SynchronousProcess::create($task, self::getId());
        }

        $process = new Process(implode(' ', [
            'exec php',
            self::$childProcessScript,
            self::$autoloader,
            self::encodeTask($task)
        ]));

        return ParallelProcess::create($process, self::getId());
    }

    /**
     * @param $task
     * @return string
     */
    public static function encodeTask($task): string
    {
        if ($task instanceof Closure) {
            $task = new SerializableClosure($task);
        }

        return base64_encode(serialize($task));
    }

    public static function decodeTask(string $task)
    {
        return unserialize(base64_decode($task));
    }

    /**
     * @return string
     */
    protected static function getId(): string
    {
        if (self::$myPid === null) {
            self::$myPid = getmypid();
        }

        self::$currentId += 1;

        return sprintf("%s%s", self::$currentId, self::$myPid);
    }
}