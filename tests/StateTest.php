<?php

namespace Denismitr\Async\Tests;

use Denismitr\Async\Tests\Stubs\TestAsyncTask;
use InvalidArgumentException;
use Denismitr\Async\WaitGroup;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    /** @test */
    public function it_can_show_a_textual_state()
    {
        $wg = WaitGroup::create();

        $wg->add(new TestAsyncTask(0));

        $this->assertContains('finished: 0', (string) $wg->state());
    }

    /** @test */
    public function it_can_show_a_textual_failed_status()
    {
        $wg = WaitGroup::create();

        foreach(range(1, 5) as $i) {
            $wg->add(function () {
                throw new \Exception('Test');
            })->catch(function () {
                // Do nothing
            });
        }

        $wg->wait();

        $this->assertContains('finished: 0', (string) $wg->state());
        $this->assertContains('failed: 5', (string) $wg->state());
        $this->assertContains('failed with Exception: Test', (string) $wg->state());
    }

    /** @test */
    public function it_can_show_timeout_status()
    {
        $wg = WaitGroup::create()->setTimeout(0);

        foreach (range(1, 5) as $i) {
            $wg->add(function () {
                sleep(1000);
            });
        }

        $wg->wait();

        $this->assertContains('timeouts: 5', (string) $wg->state());
    }
}