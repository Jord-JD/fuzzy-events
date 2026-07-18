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

    public function testInvalidThresholdsAreRejected()
    {
        $this->expectException(InvalidArgumentException::class);

        new FuzzyDispatcher([Greeting::class => ['Hello']], 101);
    }

    public function testInvalidListenerDefinitionsAreRejected()
    {
        $this->expectException(InvalidArgumentException::class);

        new FuzzyDispatcher([\stdClass::class => ['Hello']], 75);
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

    public function testRankedMatchesCanBeFiltered()
    {
        $dispatcher = new FuzzyDispatcher([Greeting::class => ['Hello']], 70);

        $this->assertSame([Greeting::class => 100.0], $dispatcher->getRankedConfidences('Hello'));
        $this->assertSame([Greeting::class => 100.0], $dispatcher->getMatches('Hello'));
        $this->assertSame([], $dispatcher->getMatches('Goodbye', 90));
    }

    public function testMatchingCanBeCaseInsensitive()
    {
        $dispatcher = new FuzzyDispatcher([Greeting::class => ['Hello']], 100, false);

        $this->assertSame(Greeting::class, $dispatcher->getListenerClassName('hello'));
    }
}
