<?php

namespace Denismitr\Async\Tests;

use Error;
use ParseError;
use Denismitr\Async\Pool;
use PHPUnit\Framework\TestCase;
use Denismitr\Async\Exceptions\ParallelException;
use Denismitr\Async\Tests\Exceptions\ClassWithSyntaxError;

class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function it_can_handle_exceptions_via_catch_callback()
    {
        $pool = Pool::create();

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

        $pool = Pool::create();

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

        $pool = Pool::create();

        $pool->add(function () {
            throw new Error('test');
        });

        $pool->wait();
    }

    /** @test */
    public function it_handles_stderr_as_parallel_error()
    {
        $pool = Pool::create();

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
        $pool = Pool::create();

        $pool->add(function () {
            new ClassWithSyntaxError();
        })->catch(function ($error) {
            $this->assertInstanceOf(ParseError::class, $error);
        });

        $pool->wait();
    }
}