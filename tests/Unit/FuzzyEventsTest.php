<?php

namespace JordJD\FuzzyEvents\Unit;

use JordJD\FuzzyEvents\Exceptions\ConfidenceTooLowException;
use JordJD\FuzzyEvents\FuzzyDispatcher;
use JordJD\FuzzyEvents\TestClasses\Greeting;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class FuzzyEventsTest extends TestCase
{
    private function getDispatcher(): FuzzyDispatcher
    {
        $listeners = [
            Greeting::class => [
                'Hello',
                'Hi',
                'Hey',
                'Greetings',
                'Howdy',
                'Hello there',
                'Hi there',
            ],
        ];

        $confidenceThreshold = 75;

        return new FuzzyDispatcher($listeners, $confidenceThreshold);
    }

    public function testEventFiring()
    {
        $response = $this->getDispatcher()->fire('Greetingz!');

        $this->assertEquals('Hello there!', $response);
    }

    public function testLowConfidenceEventFiring()
    {
        $this->expectException(ConfidenceTooLowException::class);

        $this->getDispatcher()->fire('Goodbye!');
    }

    public function testEmptyListener()
    {
        $this->expectException(InvalidArgumentException::class);

        new FuzzyDispatcher([], 75);
    }

    public function testGetListener()
    {
        $listener = $this->getDispatcher()->getListener('Why hello there');

        $this->assertInstanceOf(Greeting::class, $listener);
    }

    public function testGetListenerClassName()
    {
        $className = $this->getDispatcher()->getListenerClassName('Hello!!');

        $this->assertEquals(Greeting::class, $className);
    }

    public function testGetConfidences()
    {
        $confidences = $this->getDispatcher()->getConfidences('Hi!');

        $this->assertEquals([
            Greeting::class => 80
        ], $confidences);
    }
}
