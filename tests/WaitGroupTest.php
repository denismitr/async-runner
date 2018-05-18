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
            ->setTimeout(3);

        $timedOut = 0;

        foreach (range(1, 5) as $i) {
            $wg->add(function () use ($i) {
                sleep($i);
            })->timeout(function () use (&$timedOut) {
                $timedOut += 1;
            });
        }

        $wg->wait();

        $this->assertEquals(3, $timedOut, (string) $wg->state());
    }

    /** @test */
    public function it_can_handle_a_maximum_of_concurrent_processes()
    {
        $wg = WaitGroup::create()
            ->setMaxConcurrently(2);

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
    public function it_can_use_a_class_from_the_parent_process()
    {
        $wg = WaitGroup::create();

        /** @var TestClass $result */
        $result = null;

        $wg->add(function () {
            $class = new TestClass();

            $class->property = true;

            return $class;
        })->then(function (TestClass $class) use (&$result) {
            $result = $class;
        });

        $wg->wait();

        $this->assertInstanceOf(TestClass::class, $result);
        $this->assertTrue($result->property);
    }

    /** @test */
    public function it_returns_all_the_output_as_an_array()
    {
        $wg = WaitGroup::create();

        $result = null;

        foreach (range(1, 5) as $i) {
            $wg->add(function () {
                return 2;
            });
        }

        $result = $wg->wait();

        $this->assertCount(5, $result);
        $this->assertEquals(10, array_sum($result));
    }

    /** @test */
    public function it_can_work_with_tasks()
    {
        $wg = WaitGroup::create();

        $wg->add(new TestAsyncTask('foo', 1000));
        $wg->add(new TestAsyncTask('bar'));


        $results = $wg->wait();

        $this->assertContains('foo', $results);
        $this->assertContains('bar', $results);
    }

    /** @test */
    public function it_can_iterate_over_results()
    {
        $wg = WaitGroup::create();

        $wg->add(new TestAsyncTask(5, 200));
        $wg->add(new TestAsyncTask(7, 1000));
        $wg->add(new TestAsyncTask(15, 400));

        $results = $wg->wait();

        $sum = 0;

        foreach ($results as $result) {
            $sum += $result;
        }

        $this->assertEquals(27, $sum);
    }

    /** @test */
    public function results_are_stored_by_id()
    {
        $wg = WaitGroup::create();

        $idA = $wg->add(new TestAsyncTask('foo', 200))->getId();
        $idB = $wg->add(new TestAsyncTask('bar', 1000))->getId();
        $idC = $wg->add(new TestAsyncTask('baz', 400))->getId();

        $results = $wg->wait();

        $this->assertEquals('foo', $results[$idA]);
        $this->assertEquals('bar', $results[$idB]);
        $this->assertEquals('baz', $results[$idC]);
    }

    /** @test */
    public function synced_results_are_stored_by_id()
    {
        $wg = WaitGroup::create();

        $wg->forceSync();

        $idA = $wg->add(new TestAsyncTask('foo', 200))->getId();
        $idB = $wg->add(new TestAsyncTask('bar', 1000))->getId();
        $idC = $wg->add(new TestAsyncTask('baz', 400))->getId();

        $results = $wg->wait();

        $this->assertEquals('foo', $results[$idA]);
        $this->assertEquals('bar', $results[$idB]);
        $this->assertEquals('baz', $results[$idC]);
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

        $id = $wg->add(new Invokable(2))->getId();

        $results = $wg->wait();

        $this->assertEquals(2, $results[$id]);
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
                usleep(100);

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
    public function it_will_automatically_schedule_synchronous_tasks_when_must_be_sync()
    {
        $wg = WaitGroup::create();

        $wg->forceSync();

        $wg->add(new TestAsyncTask(0))->then(function ($output) {
            $this->assertEquals(0, $output);
        });

        $wg->wait();
    }
}