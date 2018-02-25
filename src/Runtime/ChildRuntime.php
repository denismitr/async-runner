<?php

use Denismitr\Async\Runtime\ParentRuntime;

try {
    $autoloader = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;

    if ( ! $autoloader) {
        throw new InvalidArgumentException('No autoloader provided in child process.');
    }

    if ( ! file_exists($autoloader)) {
        throw new InvalidArgumentException("Could not find autoloader in child process: {$autoloader}");
    }

    if ( ! $serializedClosure) {
        throw new InvalidArgumentException('No valid closure was passed to the child process.');
    }

    require_once $autoloader;

    $task = ParentRuntime::decodeTask($serializedClosure);

    $output = call_user_func($task);
    $serializedOutput = base64_encode(serialize($output));

    $outputLength = 1024 * 10;

    if (strlen($serializedOutput) > $outputLength) {
        throw \Denismitr\Async\Exceptions\ParallelException::outputTooLarge(strlen($serializedOutput), $outputLength);
    }

    fwrite(STDOUT, $serializedOutput);

    exit(0);
} catch (Throwable $t) {
    require_once __DIR__.'/../Exceptions/SerializableException.php';

    $output = new \Denismitr\Async\Exceptions\SerializableException($t);

    fwrite(STDERR, base64_encode(serialize($output)));

    exit(1);
}