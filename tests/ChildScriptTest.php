<?php

namespace Denismitr\Async\Tests;


use Opis\Closure\SerializableClosure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ChildScriptTest extends TestCase
{
    /** @test */
    public function it_runs()
    {
        $bootstrap = __DIR__ . '/../src/Runtime/child_script.php';
        $autoloader = __DIR__.'/../vendor/autoload.php';

        $serializedClosure = base64_encode(serialize(new SerializableClosure(function () {
            echo 'child process';
        })));

        $process = new Process("php {$bootstrap} {$autoloader} {$serializedClosure}");

        $process->start();
        $process->wait();

        $this->assertContains('child process', $process->getOutput());
    }
}