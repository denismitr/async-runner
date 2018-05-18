<?php

namespace Denismitr\Async\Tests;

use Denismitr\Async\Tests\Exceptions\TestException;
use Error;
use ParseError;
use Denismitr\Async\WaitGroup;
use PHPUnit\Framework\TestCase;
use Denismitr\Async\Exceptions\ParallelException;
use Denismitr\Async\Tests\Exceptions\ClassWithSyntaxError;

class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function it_can_handle_exceptions_via_catch_callback()
    {
        $pool = WaitGroup::create();

        foreach (range(1, 5) as $i) {
            $pool->add(function () {
                throw new TestException('test');
            })->catch(function (TestException $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });
        }

        $pool->wait();

        $this->assertCount(5, $pool->getFailed(), (string) $pool->state());
    }

    /** @test */
    public function it_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(TestException::class);
        $this->expectExceptionMessageRegExp('/test/');

        $pool = WaitGroup::create();

        $pool->add(function () {
            throw new TestException('test');
        });

        $pool->wait();
    }

    /** @test */
    public function it_throws_fatal_errors()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessageRegExp('/test/');

        $pool = WaitGroup::create();

        $pool->add(function () {
            throw new Error('test');
        });

        $pool->wait();
    }

    /** @test */
    public function it_handles_stderr_as_parallel_error()
    {
        $pool = WaitGroup::create();

        $pool->add(function () {
            fwrite(STDERR, 'test');
        })->catch(function (ParallelException $e) {
            $this->assertContains('test', $e->getMessage());
        });

        $pool->wait();
    }
    
    /** @test */
    public function deep_syntax_errors_are_thrown()
    {
        $pool = WaitGroup::create();

        $pool->add(function () {
            new ClassWithSyntaxError();
        })->catch(function ($error) {
            $this->assertInstanceOf(ParseError::class, $error);
        });

        $pool->wait();
    }
}