<?php

namespace Denismitr\Async\Tests;

use Denismitr\Async\Tests\Stubs\TestTask;
use InvalidArgumentException;
use Denismitr\Async\WaitGroup;
use PHPUnit\Framework\TestCase;

class stateTest extends TestCase
{
    /** @test */
    public function it_can_show_a_textual_state()
    {
        $pool = WaitGroup::make();

        $pool->add(new TestTask());

        $this->assertContains('finished: 0', (string) $pool->state());
    }

    /** @test */
    public function it_can_show_a_textual_failed_status()
    {
        $pool = WaitGroup::make();

        foreach(range(1, 5) as $i) {
            $pool->add(function () {
                throw new \Exception('Test');
            })->catch(function () {
                // Do nothing
            });
        }

        $pool->wait();

        $this->assertContains('finished: 0', (string) $pool->state());
        $this->assertContains('failed: 5', (string) $pool->state());
        $this->assertContains('failed with Exception: Test', (string) $pool->state());
    }

    /** @test */
    public function it_can_show_timeout_status()
    {
        $pool = WaitGroup::make()->setTimeout(0);

        foreach (range(1, 5) as $i) {
            $pool->add(function () {
                sleep(1000);
            });
        }

        $pool->wait();

        $this->assertContains('timeouts: 5', (string) $pool->state());
    }
}