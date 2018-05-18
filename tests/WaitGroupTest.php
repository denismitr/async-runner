<?php

namespace Denismitr\Async\Tests;


use Denismitr\Async\Tests\Stubs\Invokable;
use Denismitr\Async\Tests\Stubs\NonInvokable;
use Denismitr\Async\Tests\Stubs\TestClass;
use Denismitr\Async\Tests\Stubs\TestAsyncTask;
use Denismitr\Async\WaitGroup;
use Denismitr\Async\Process\SynchronousProcess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

class WaitGroupTest extends TestCase
{
    /** @var \Symfony\Component\Stopwatch\Stopwatch */
    protected $stopwatch;

    public function setUp()
    {
        parent::setUp();

        $supported = WaitGroup::isSupported();

        if ( ! $supported) {
            $this->markTestSkipped('Extensions `posix` and `pcntl` are not supported.');
        }

        $this->stopwatch = new Stopwatch();
    }
    
    /** @test */
    public function it_can_tun_processes_in_parallel()
    {
        $wg = WaitGroup::create();

        $this->stopwatch->start('test');

        foreach (range(1, 5) as $i) {
            $wg->add(function () {
                usleep(1000);
            });
        }

        $wg->wait();

        $stopwatchResult = $this->stopwatch->stop('test');

        $this->assertLessThan(
            900,
            $stopwatchResult->getDuration(),
            "Execution time was {$stopwatchResult->getDuration()}, expected less than 400.\n".(string) $wg->state()
        );
    }

    /** @test */
    public function it_can_handle_success()
    {
        $wg = WaitGroup::create();
        $profit = 0;

        foreach (range(1, 10) as $i) {
            $wg->add(function () {
                return 5;
            })->then(function (int $result) use (&$profit) {
                $profit += $result;
            });
        }

        $wg->wait();

        $this->assertEquals(50, $profit, (string) $wg->state());
    }

    /** @test */
    public function it_can_handle_timeout()
    {
        $wg = WaitGroup::create()
            ->setTimeout(1);

        $counter = 0;

        foreach (range(1, 5) as $i) {
            $wg->add(function () {
                sleep(2);
            })->timeout(function () use (&$counter) {
                $counter += 1;
            });
        }

        $wg->wait();

        $this->assertEquals(5, $counter, (string) $wg->state());
    }

    /** @test */
    public function it_can_handle_a_maximum_of_concurrent_processes()
    {
        $wg = WaitGroup::create()
            ->concurrency(2);

        $startTime = microtime(true);

        foreach (range(1, 3) as $i) {
            $wg->add(function () {
                sleep(1);
            });
        }

        $wg->wait();

        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $this->assertGreaterThanOrEqual(2, $executionTime, "Execution time was {$executionTime}, expected more than 2.\n".(string) $wg->state());
        $this->assertCount(3, $wg->getFinished(), (string) $wg->state());
    }

    /** @test */
    public function it_works_with_helper_functions()
    {
        $wg = WaitGroup::create();

        $counter = 0;

        foreach (range(1, 5) as $i) {
            $wg[] = async(function () {
                usleep(random_int(10, 1000));
                return 2;
            })->then(function (int $output) use (&$counter) {
                $counter += $output;
            });
        }

        await($wg);

        $this->assertEquals(10, $counter, (string) $wg->state());
    }

    /** @test */
    public function it_can_use_a_class_from_the_parent_process()
    {
        $wg = WaitGroup::create();

        /** @var TestClass $result */
        $result = null;

        $wg[] = async(function () {
            $class = new TestClass();

            $class->property = true;

            return $class;
        })->then(function (TestClass $class) use (&$result) {
            $result = $class;
        });

        await($wg);

        $this->assertInstanceOf(TestClass::class, $result);
        $this->assertTrue($result->property);
    }

    /** @test */
    public function it_returns_all_the_output_as_an_array()
    {
        $wg = WaitGroup::create();

        $result = null;

        foreach (range(1, 5) as $i) {
            $wg[] = async(function () {
                return 2;
            });
        }

        $result = await($wg);

        $this->assertCount(5, $result);
        $this->assertEquals(10, array_sum($result));
    }

    /** @test */
    public function it_can_work_with_tasks()
    {
        $wg = WaitGroup::create();

        $wg[] = async(new TestAsyncTask('foo', 1000));
        $wg[] = async(new TestAsyncTask('bar'));

        $results = await($wg);

        $this->assertEquals('foo', $results[0]);
        $this->assertEquals('bar', $results[1]);
    }

    /** @test */
    public function it_can_accept_tasks_with_wg_add()
    {
        $wg = WaitGroup::create();

        $wg->add(new TestAsyncTask(2));

        $results = await($wg);

        $this->assertEquals(2, $results[0]);
    }

    /** @test */
    public function it_can_check_for_asynchronous_support()
    {
        $this->assertTrue(WaitGroup::isSupported());
    }

    /** @test */
    public function it_can_run_invokable_classes()
    {
        $wg = WaitGroup::create();

        $wg->add(new Invokable());

        $results = await($wg);
        $this->assertEquals(2, $results[0]);
    }

    /** @test */
    public function it_reports_error_for_non_invokable_classes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $wg = WaitGroup::create();

        $wg->add(new NonInvokable());
    }

    public function it_can_run_synchronous_processes()
    {
        $wg = WaitGroup::create();

        $this->stopwatch->start('test');

        foreach (range(1, 3) as $i) {
            $wg->add(new SynchronousProcess(function () {
                sleep(1);
                return 2;
            }, $i))->then(function ($output) {
                $this->assertEquals(2, $output);
            });
        }

        $wg->wait();

        $stopwatchResult = $this->stopwatch->stop('test');

        $this->assertGreaterThan(3000, $stopwatchResult->getDuration(), "Execution time was {$stopwatchResult->getDuration()}, expected less than 3000.\n".(string) $wg->status());
    }

    /** @test */
    public function it_will_automatically_schedule_synchronous_tasks_if_pcntl_not_supported()
    {
        WaitGroup::$forceSync = true;

        $wg = WaitGroup::create();

        $wg[] = async(new TestAsyncTask(0))->then(function ($output) {
            $this->assertEquals(0, $output);
        });

        await($wg);

        WaitGroup::$forceSync = false;
    }

    /** @test */
    public function it_takes_an_intermediate_callback()
    {
        $wg = WaitGroup::create();

        $wg[] = async(function () {
            return 1;
        });

        $isIntermediateCallbackCalled = false;

        $wg->wait(function (WaitGroup $wg) use (&$isIntermediateCallbackCalled) {
            $isIntermediateCallbackCalled = true;
        });

        $this->assertTrue($isIntermediateCallbackCalled);
    }
}